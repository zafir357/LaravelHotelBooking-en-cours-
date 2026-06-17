<?php

namespace App\Services;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\RoomRepositoryInterface;
use App\Events\BookingCreated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
        private RoomRepositoryInterface $roomRepository,
        private PaymentService $paymentService,
    ) {}

    /**
     * @return Collection<int, Booking>
     */
    public function getAllBookings(): Collection
    {
        return $this->bookingRepository->all();
    }

    public function findById(int $id): ?Booking
    {
        return $this->bookingRepository->findById($id);
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getUserBookings(int $userId): Collection
    {
        return $this->bookingRepository->findByUser($userId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createBooking(array $data): Booking
    {
        $available = $this->roomRepository->getAvailable($data['check_in'], $data['check_out']);

        if ($available->where('id', $data['room_id'])->isEmpty()) {
            throw ValidationException::withMessages([
                'room_id' => 'This room is not available for the selected dates.',
            ]);
        }

        $room = $this->roomRepository->findById($data['room_id']);
        $totalPrice = $room->calculateTotalPrice($data['check_in'], $data['check_out']);

        // Vérifie le paiement Stripe AVANT toute écriture en base — si le
        // paiement n'est pas valide, on s'arrête ici, aucune réservation
        // n'est créée. C'est ça qui remplace l'ancien statut "pending en
        // attente d'approbation manuelle" : le paiement réussi EST la
        // confirmation, il n'y a plus d'étape intermédiaire à valider par le staff.
        $this->paymentService->verify($data['payment_intent_id'], $totalPrice);

        // DB::transaction() : si la création du Payment échoue après celle
        // du Booking (panne DB par exemple), les deux sont annulées ensemble
        // plutôt que de se retrouver avec une réservation "confirmée" sans
        // aucune trace de paiement associée.
        $booking = DB::transaction(function () use ($data, $totalPrice) {
            $booking = $this->bookingRepository->create([
                'user_id' => $data['user_id'],
                'room_id' => $data['room_id'],
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'notes' => $data['notes'] ?? null,
                'total_price' => $totalPrice,
                'status' => 'confirmed',
            ]);

            $this->paymentService->record($booking, $data['payment_intent_id']);

            return $booking;
        });

        event(new BookingCreated($booking));

        return $booking;
    }

    public function cancelBooking(int $id): Booking
    {
        $booking = $this->bookingRepository->findById($id);

        // Remboursement automatique si la réservation avait été payée —
        // voir PaymentService::refund() pour pourquoi ça n'empêche jamais
        // l'annulation elle-même de réussir, même si Stripe est indisponible.
        if ($booking->payment !== null) {
            $this->paymentService->refund($booking->payment);
        }

        return $this->bookingRepository->update($id, ['status' => 'cancelled']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBooking(int $id, array $data): Booking
    {
        return $this->bookingRepository->update($id, $data);
    }
}
