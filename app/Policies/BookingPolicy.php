<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    // Voir toutes les réservations
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isReceptionist();
    }

    // Voir une réservation spécifique
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin()
            || $user->isReceptionist()
            || $user->id === $booking->user_id;
    }

    // Créer une réservation
    public function create(User $user): bool
    {
        return $user->isGuest();
    }

    // Modifier une réservation (status, notes)
    public function update(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isReceptionist()) {
            return in_array($booking->status, ['pending', 'confirmed']);
        }

        // Guest peut modifier seulement la sienne si pending
        return $user->id === $booking->user_id
            && $booking->status === 'pending';
    }

    // Annuler une réservation
    public function delete(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Guest peut annuler seulement la sienne si pending
        return $user->id === $booking->user_id
            && $booking->status === 'pending';
    }
}
