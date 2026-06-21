<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    // Tout le monde peut voir les rooms
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Room $room): bool
    {
        return true;
    }

    // Seule la réceptionniste peut créer/modifier/supprimer une chambre.
    public function create(User $user): bool
    {
        return $user->isReceptionist();
    }

    public function update(User $user, Room $room): bool
    {
        return $user->isReceptionist();
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->isReceptionist();
    }
}
