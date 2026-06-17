<?php

namespace App\Repositories\Contracts;

use App\Models\Room;
use Illuminate\Support\Collection;

interface RoomRepositoryInterface
{
    public function all(): Collection;
    public function findById(int $id): ?Room;
    public function getAvailable(string $checkIn, string $checkOut): Collection;
    public function create(array $data): Room;
    public function update(int $id, array $data): Room;
    public function delete(int $id): bool;
}
