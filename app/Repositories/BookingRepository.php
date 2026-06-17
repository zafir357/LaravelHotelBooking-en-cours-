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
        // ->fresh() : sans ça, l'objet retourné ne contient QUE les champs
        // qu'on a explicitement passés dans $data (room_id, check_in...).
        // "status" n'en fait pas partie (il vient du défaut 'pending' de la
        // colonne en base) — donc l'objet en mémoire avait status = null
        // juste après création, même si la ligne en base était correcte.
        // fresh() relit la ligne depuis la base pour avoir toutes les vraies
        // valeurs, y compris celles appliquées par défaut côté SQL.
        return Booking::create($data)->fresh();
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
