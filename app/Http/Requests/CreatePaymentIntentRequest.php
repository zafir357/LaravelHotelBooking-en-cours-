<?php

namespace App\Http\Requests;

use App\Concerns\ValidatesRoomAvailability;
use App\Models\Booking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentIntentRequest extends FormRequest
{
    use ValidatesRoomAvailability;

    /**
     * Même règle d'autorisation que pour créer une réservation : seul un
     * guest peut initier un paiement (pas de sens pour receptionist).
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Booking::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_id' => $this->roomAvailabilityRules(),
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
        ];
    }
}
