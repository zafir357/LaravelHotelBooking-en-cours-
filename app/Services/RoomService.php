<?php

namespace App\Services;

use App\Models\Room;
use App\Repositories\Contracts\RoomRepositoryInterface;
use Illuminate\Support\Collection;

class RoomService
{
    public function __construct(
        private RoomRepositoryInterface $roomRepository,
    ) {}

    public function getAllRooms(): Collection
    {
        return $this->roomRepository->all();
    }

    public function getAvailableRooms(string $checkIn, string $checkOut): Collection
    {
        return $this->roomRepository->getAvailable($checkIn, $checkOut);
    }

    public function createRoom(array $data): Room
    {
        return $this->roomRepository->create($data);
    }

    public function updateRoom(int $id, array $data): Room
    {
        return $this->roomRepository->update($id, $data);
    }

    public function deleteRoom(int $id): bool
    {
        return $this->roomRepository->delete($id);
    }
}
