<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use Illuminate\Support\Collection;

interface BookingRepositoryInterface
{
    public function all(): Collection;
    public function findById(int $id): ?Booking;
    public function findByUser(int $userId): Collection;
    public function create(array $data): Booking;
    public function update(int $id, array $data): Booking;
    public function delete(int $id): bool;
}
