<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Support\Collection;

class BookingRepository implements BookingRepositoryInterface
{
    /**
     * @return Collection<int, Booking>
     */
    public function all(): Collection
    {
        return Booking::with(['user', 'room', 'payment'])->get();
    }

    public function findById(int $id): ?Booking
    {
        return Booking::with(['user', 'room', 'payment'])->findOrFail($id);
    }

    /**
     * @return Collection<int, Booking>
     */
    public function findByUser(int $userId): Collection
    {
        return Booking::with(['room', 'payment'])
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Booking
    {
        $booking = Booking::findOrFail($id);
        $booking->update($data);
        return $booking->fresh();
    }

    public function delete(int $id): bool
    {
        return Booking::findOrFail($id)->delete();
    }
}
