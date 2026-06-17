<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    // Voir la liste des réservations — appelé pour TOUT utilisateur listant
    // des réservations, pas seulement admin/receptionist. Le filtrage
    // "toutes" vs "les siennes uniquement" est fait dans
    // BookingController::index() (selon $user->isGuest()), pas ici : cette
    // permission ne décide que "a-t-il le droit de lister, point" — d'où
    // true pour tout le monde. Avant ce fix, un guest recevait un 403 sur
    // GET /api/bookings, donc "My bookings" n'affichait jamais rien.
    public function viewAny(User $user): bool
    {
        return true;
    }

    // Voir une réservation spécifique
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin()
            || $user->isReceptionist()
            || $user->id === $booking->user_id;
    }

    // Créer une réservation
    public function create(User $user): bool
    {
        return $user->isGuest();
    }

    // Modifier une réservation (status, notes)
    public function update(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isReceptionist()) {
            return in_array($booking->status, ['pending', 'confirmed']);
        }

        // Guest peut modifier seulement la sienne, tant qu'elle n'est pas
        // déjà terminée/annulée. "pending" reste listé pour compatibilité —
        // ce statut n'apparaît plus dans le flow normal depuis que le
        // paiement Stripe confirme directement la réservation, mais la
        // colonne existe toujours dans l'enum pour d'éventuelles évolutions futures.
        return $user->id === $booking->user_id
            && in_array($booking->status, ['pending', 'confirmed']);
    }

    // Annuler une réservation
    public function delete(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Receptionist peut annuler, mais pas une réservation déjà terminée
        // (même contrainte que pour update() — une fois "completed", plus
        // aucune action de gestion n'a de sens dessus).
        if ($user->isReceptionist()) {
            return in_array($booking->status, ['pending', 'confirmed']);
        }

        // Guest peut annuler seulement la sienne, tant qu'elle n'est pas
        // déjà terminée/annulée (voir commentaire similaire dans update()).
        return $user->id === $booking->user_id
            && in_array($booking->status, ['pending', 'confirmed']);
    }
}
