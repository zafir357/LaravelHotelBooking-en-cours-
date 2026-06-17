<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\Room;
use App\Repositories\Contracts\RoomRepositoryInterface;
use Illuminate\Support\Collection;

class RoomRepository implements RoomRepositoryInterface
{
    /**
     * @return Collection<int, Room>
     */
    public function all(): Collection
    {
        return Room::all();
    }

    public function findById(int $id): ?Room
    {
        return Room::findOrFail($id);
    }

/**
 * @return Collection<int, Room>
 */
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
    /**
     * Construit dynamiquement une requête SQL selon les filtres reçus depuis
     * la page Rooms.vue (type, prix min/max, capacité, dates).
     *
     * Pourquoi ->when() plutôt que des "if" classiques avant la requête ?
     * ->when($condition, $callback) est une méthode Eloquent qui applique le
     * callback SEULEMENT si $condition est "truthy" (pas null, pas false, pas 0).
     * Ça permet de chaîner les filtres optionnels sans casser le ->query() de base,
     * et sans avoir à réassigner $query = $query->where(...) à chaque "if".
     * Concrètement : si l'utilisateur n'a pas choisi de type sur la page,
     * $filters['type'] est null -> le ->when() ignore simplement ce filtre,
     * et la requête SQL ne contient pas de "WHERE type = ...".
     *
     * @param  array<string, mixed>  $filters  Les filtres bruts venant de la requête HTTP
     *                                          (ex: ['type' => 'suite', 'min_price' => 100])
     * @return Collection<int, Room>
     */
    public function filter(array $filters): Collection
    {
        return Room::query()
            // Une chambre marquée indisponible (ex: en maintenance) ne doit
            // jamais apparaître dans les résultats de recherche, peu importe les filtres.
            ->where('is_available', true)

            // Filtre par type de chambre (single/double/suite) — appliqué seulement
            // si l'utilisateur a sélectionné un type dans le <v-select> du frontend.
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))

            // Prix minimum/maximum par nuit — deux filtres indépendants pour
            // permettre une fourchette de prix (ex: entre 100€ et 250€).
            ->when($filters['min_price'] ?? null, fn ($query, $price) => $query->where('price_per_night', '>=', $price))
            ->when($filters['max_price'] ?? null, fn ($query, $price) => $query->where('price_per_night', '<=', $price))

            // "capacity" = nombre de voyageurs. On utilise >= car une chambre prévue
            // pour 4 personnes peut très bien convenir à une recherche "2 personnes".
            ->when($filters['capacity'] ?? null, fn ($query, $capacity) => $query->where('capacity', '>=', $capacity))

            // Disponibilité sur des dates précises — seulement si LES DEUX dates
            // (check_in ET check_out) sont fournies, sinon ça n'a pas de sens de filtrer.
            // whereDoesntHave('bookings', ...) = exclut les chambres qui ont déjà
            // une réservation (non annulée) qui chevauche la période demandée.
            // La logique de chevauchement : deux périodes [A_debut, A_fin] et [B_debut, B_fin]
            // se chevauchent si A_debut < B_fin ET A_fin > B_debut.
            ->when(
                ($filters['check_in'] ?? null) && ($filters['check_out'] ?? null),
                fn ($query) => $query->whereDoesntHave('bookings', function ($q) use ($filters) {
                    $q->whereNotIn('status', ['cancelled'])
                        ->where('check_in', '<', $filters['check_out'])
                        ->where('check_out', '>', $filters['check_in']);
                })
            )
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Room
    {
        return Room::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
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
