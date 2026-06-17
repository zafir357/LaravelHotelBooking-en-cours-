<?php

namespace App\Repositories\Contracts;

use App\Models\Room;
use Illuminate\Support\Collection;

interface RoomRepositoryInterface
{
    /**
     * @return Collection<int, Room>
     */
    public function all(): Collection;

    public function findById(int $id): ?Room;

    /**
     * @return Collection<int, Room>
     */
    public function getAvailable(string $checkIn, string $checkOut): Collection;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Room>
     */
    public function filter(array $filters): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Room;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Room;

    public function delete(int $id): bool;
}
