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
            'check_in'    => $this->check_in->toDateString(),
            'check_out'   => $this->check_out->toDateString(),
            'total_price' => $this->total_price,
            'status'      => $this->status,
            'notes'       => $this->notes,
            'created_at'  => $this->created_at->toDateTimeString(),
        ];
    }
}
