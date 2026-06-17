<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'type'            => $this->type,
            'description'     => $this->description,
            'price_per_night' => $this->price_per_night,
            'capacity'        => $this->capacity,
            'is_available'    => $this->is_available,
            'images'          => $this->images ?? [],
            'created_at'      => $this->created_at->toDateTimeString(),
        ];
    }
}
