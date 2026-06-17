<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'room'        => new RoomResource($this->whenLoaded('room')),
            // whenLoaded() : n'inclut "user" dans la réponse que si la
            // relation a été eager-loaded (BookingRepository::all() le fait,
            // findByUser() non — inutile pour un guest de voir SON propre nom
            // répété sur chacune de ses réservations).
            'user'        => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),
            'check_in'    => $this->check_in->toDateString(),
            'check_out'   => $this->check_out->toDateString(),
            'total_price' => $this->total_price,
            'status'      => $this->status,
            'notes'       => $this->notes,
            'created_at'  => $this->created_at->toDateTimeString(),
        ];
    }
}
