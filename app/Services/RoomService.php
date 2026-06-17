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

    /**
     * @return Collection<int, Room>
     */
    public function getAllRooms(): Collection
    {
        return $this->roomRepository->all();
    }

    public function findById(int $id): ?Room
    {
        return $this->roomRepository->findById($id);
    }

    /**
     * @return Collection<int, Room>
     */
    public function getAvailableRooms(string $checkIn, string $checkOut): Collection
    {
        return $this->roomRepository->getAvailable($checkIn, $checkOut);
    }

    // Le Service ne contient AUCUNE logique de filtrage lui-même — il se contente
    // de transmettre les filtres au Repository. Pourquoi cette couche existe alors ?
    // C'est ici qu'on ajouterait, plus tard, une logique métier qui ne concerne pas
    // directement la base de données : par exemple "si l'utilisateur n'est pas connecté,
    // cacher le prix exact" ou "logger chaque recherche pour des statistiques".
    // Le Controller ne parle jamais directement au Repository : il passe toujours
    // par ce Service, qui lui reste la seule porte d'entrée vers les données des chambres.
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Room>
     */
    public function filterRooms(array $filters): Collection
    {
        return $this->roomRepository->filter($filters);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRoom(array $data): Room
    {
        return $this->roomRepository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRoom(int $id, array $data): Room
    {
        return $this->roomRepository->update($id, $data);
    }

    public function deleteRoom(int $id): bool
    {
        return $this->roomRepository->delete($id);
    }
}
