<?php

namespace App\Concerns;

use App\Models\Room;
use Closure;

trait ValidatesRoomAvailability
{
    /**
     * Règle de validation pour room_id : vérifie qu'aucune réservation
     * existante (autre que "cancelled") ne chevauche les dates demandées.
     * Utilisée à la fois pour créer un PaymentIntent (CreatePaymentIntentRequest)
     * et pour créer la réservation elle-même (StoreBookingRequest) — ces deux
     * étapes doivent appliquer EXACTEMENT la même règle, sinon on pourrait
     * créer un PaymentIntent pour une chambre qu'on refuserait ensuite de
     * réserver, ou l'inverse.
     *
     * @return array<int, string|Closure>
     */
    protected function roomAvailabilityRules(): array
    {
        return [
            'required',
            'exists:rooms,id',
            function (string $attribute, mixed $value, Closure $fail): void {
                $checkIn = $this->input('check_in');
                $checkOut = $this->input('check_out');

                if (! $checkIn || ! $checkOut) {
                    return;
                }

                $room = Room::find((int) $value);

                if ($room === null) {
                    return;
                }

                $overlaps = $room->bookings()
                    ->whereNotIn('status', ['cancelled'])
                    ->where('check_in', '<', $checkOut)
                    ->where('check_out', '>', $checkIn)
                    ->exists();

                if ($overlaps) {
                    $fail('This room is not available for the selected dates.');
                }
            },
        ];
    }
}
