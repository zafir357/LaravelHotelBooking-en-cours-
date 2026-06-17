<?php

namespace App\Services;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\RoomRepositoryInterface;
use App\Events\BookingCreated;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
        private RoomRepositoryInterface $roomRepository,
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
        $nights = now()->parse($data['check_in'])->diffInDays($data['check_out']);
        $data['total_price'] = $room->price_per_night * $nights;

        $booking = $this->bookingRepository->create($data);

        event(new BookingCreated($booking));

        return $booking;
    }

    public function cancelBooking(int $id): Booking
    {
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
