<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class PaymentService
{
    private StripeClient $stripe;

    public function __construct()
    {
        // StripeClient (pas la façade statique Stripe::setApiKey(...)) pour
        // pouvoir injecter un client différent dans les tests plus tard sans
        // dépendre d'un état global partagé entre toutes les requêtes.
        $this->stripe = new StripeClient(config('services.stripe.key'));
    }

    /**
     * Crée un PaymentIntent Stripe pour le montant exact calculé côté serveur
     * (jamais un montant envoyé par le client — voir Room::calculateTotalPrice()).
     *
     * @return array{client_secret: string, payment_intent_id: string, amount: float}
     */
    public function createIntent(Room $room, string $checkIn, string $checkOut): array
    {
        $amount = $room->calculateTotalPrice($checkIn, $checkOut);

        try {
            // Stripe travaille en plus petite unité monétaire (centimes pour
            // l'EUR), jamais en décimal — d'où le * 100. round() évite les
            // erreurs d'arrondi flottant (ex: 19.99 * 100 = 1998.9999...).
            $intent = $this->stripe->paymentIntents->create([
                'amount' => (int) round($amount * 100),
                'currency' => 'eur',
                // Par défaut, Stripe active automatiquement tous les moyens
                // de paiement configurés sur le compte (carte, mais aussi
                // Klarna, Bancontact, etc.), dont certains exigent une
                // redirection hors de la page. 'allow_redirects' => 'never'
                // les désactive : seuls les moyens de paiement qui peuvent
                // se confirmer sans quitter la page (carte, dans notre cas)
                // restent disponibles — cohérent avec le `redirect:
                // 'if_required'` côté frontend (BookingDialog.vue).
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
                'metadata' => [
                    'room_id' => (string) $room->id,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                ],
            ]);
        } catch (ApiErrorException $e) {
            throw ValidationException::withMessages([
                'payment' => 'Unable to start payment: '.$e->getMessage(),
            ]);
        }

        return [
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
            'amount' => $amount,
        ];
    }

    /**
     * Vérifie auprès de Stripe (pas seulement en local) qu'un PaymentIntent a
     * réellement réussi et correspond bien au montant attendu. Ne crée RIEN
     * en base — c'est volontairement séparé de record() : on doit pouvoir
     * vérifier le paiement AVANT de créer la réservation (sinon on n'a pas
     * encore de booking_id à associer), donc cette méthode ne reçoit que le
     * montant attendu, pas un Booking.
     *
     * @throws ValidationException si le paiement n'est pas valide
     */
    public function verify(string $paymentIntentId, float $expectedAmount): void
    {
        // Un même PaymentIntent ne doit jamais pouvoir servir à "payer" deux
        // réservations différentes (ex: un utilisateur qui rejoue la requête).
        if (Payment::where('transaction_id', $paymentIntentId)->exists()) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'This payment has already been used.',
            ]);
        }

        try {
            $intent = $this->stripe->paymentIntents->retrieve($paymentIntentId);
        } catch (ApiErrorException $e) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'Unable to verify payment: '.$e->getMessage(),
            ]);
        }

        if ($intent->status !== PaymentIntent::STATUS_SUCCEEDED) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'Payment has not been completed.',
            ]);
        }

        $expectedAmountInCents = (int) round($expectedAmount * 100);

        if ($intent->amount_received !== $expectedAmountInCents) {
            throw ValidationException::withMessages([
                'payment_intent_id' => 'Payment amount does not match the booking total.',
            ]);
        }
    }

    // Appelée APRÈS verify() ET après la création de la réservation (on a
    // besoin du booking_id). Ne refait aucune vérification — record() fait
    // confiance à verify() déjà passé juste avant dans le même flow.
    public function record(Booking $booking, string $paymentIntentId): Payment
    {
        return Payment::create([
            'booking_id' => $booking->id,
            'amount' => $booking->total_price,
            'status' => 'paid',
            'transaction_id' => $paymentIntentId,
            'paid_at' => now(),
        ]);
    }

    // Remboursement déclenché à l'annulation d'une réservation déjà payée
    // (voir BookingService::cancelBooking()). Volontairement non bloquant :
    // si Stripe est indisponible, l'annulation elle-même doit pouvoir se
    // terminer quand même — le remboursement pourrait être retraité
    // manuellement plus tard, mais ce n'est pas grave si la réservation
    // reste annulée même si l'appel Stripe échoue ponctuellement.
    public function refund(Payment $payment): void
    {
        if ($payment->status !== 'paid' || $payment->transaction_id === null) {
            return;
        }

        try {
            $this->stripe->refunds->create([
                'payment_intent' => $payment->transaction_id,
            ]);

            $payment->update(['status' => 'refunded']);
        } catch (ApiErrorException) {
            // Volontairement avalé — voir le commentaire au-dessus de la méthode.
        }
    }
}
