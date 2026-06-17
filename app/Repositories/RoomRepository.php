<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\Room;
use App\Repositories\Contracts\RoomRepositoryInterface;
use Illuminate\Support\Collection;

class RoomRepository implements RoomRepositoryInterface
{
    public function all(): Collection
    {
        return Room::all();
    }

    public function findById(int $id): ?Room
    {
        return Room::findOrFail($id);
    }

public function getAvailable(string $checkIn, string $checkOut): Collection
{
    return Room::where('is_available', true)
        ->whereDoesntHave('bookings', function ($query) use ($checkIn, $checkOut) {
            $query->whereNotIn('status', ['cancelled'])
                ->where('check_in', '<', $checkOut)
                ->where('check_out', '>', $checkIn);
        })
        ->get();
}
    public function create(array $data): Room
    {
        return Room::create($data);
    }

    public function update(int $id, array $data): Room
    {
        $room = Room::findOrFail($id);
        $room->update($data);
        return $room->fresh();
    }

    public function delete(int $id): bool
    {
        return Room::findOrFail($id)->delete();
    }
}
