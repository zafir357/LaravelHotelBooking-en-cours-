<?php

namespace App\Http\Requests;

use App\Concerns\ValidatesRoomAvailability;
use App\Models\Booking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    use ValidatesRoomAvailability;

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
            'room_id'           => $this->roomAvailabilityRules(),
            'check_in'          => 'required|date|after:today',
            'check_out'         => 'required|date|after:check_in',
            'notes'             => 'nullable|string|max:500',
            // Preuve qu'un paiement Stripe a été initié — vérifié réellement
            // (statut + montant) côté serveur dans PaymentService::verifyAndRecord(),
            // jamais sur la seule base de cette chaîne envoyée par le client.
            'payment_intent_id' => 'required|string',
        ];
    }
}
