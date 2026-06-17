<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use Illuminate\Support\Collection;

interface BookingRepositoryInterface
{
    /**
     * @return Collection<int, Booking>
     */
    public function all(): Collection;

    public function findById(int $id): ?Booking;

    /**
     * @return Collection<int, Booking>
     */
    public function findByUser(int $userId): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Booking;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Booking;

    public function delete(int $id): bool;
}
