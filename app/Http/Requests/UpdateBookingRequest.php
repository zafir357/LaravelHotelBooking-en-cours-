<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->booking);
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:pending,confirmed,cancelled,completed',
            'notes'  => 'nullable|string|max:500',
        ];
    }
}
