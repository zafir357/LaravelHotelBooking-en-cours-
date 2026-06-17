<?php

namespace App\Http\Requests;

use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
   public function authorize(): bool
    {
           return $this->user()->can('create', Room::class);
    }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:single,double,suite',
            'description'     => 'nullable|string',
            'price_per_night' => 'required|numeric|min:1',
            'capacity'        => 'required|integer|min:1',
            'images'          => 'nullable|array',
            'images.*'        => 'url',
        ];
    }
}
