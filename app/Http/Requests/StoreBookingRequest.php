<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Booking::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
     public function rules(): array
    {
        return [
            'room_id'    => [
                'required',
                'exists:rooms,id',
                // Closure de validation : Laravel l'appelle avec (nom du
                // champ, valeur du champ, callback $fail à utiliser si invalide).
                // On vérifie ici qu'aucune réservation existante (autre que
                // "cancelled") ne chevauche les dates demandées pour CETTE
                // chambre précise — avant, cette vérification n'existait
                // qu'à l'intérieur de BookingService::createBooking(), sans
                // message d'erreur clair rattaché au bon champ du formulaire.
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $checkIn = $this->input('check_in');
                    $checkOut = $this->input('check_out');

                    // Si les dates elles-mêmes sont absentes/invalides, les
                    // règles dédiées check_in/check_out s'en chargeront déjà
                    // — pas la peine de tenter une requête avec des valeurs vides.
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
            ],
            'check_in'   => 'required|date|after:today',
            'check_out'  => 'required|date|after:check_in',
            'notes'      => 'nullable|string|max:500',
        ];
    }
}
