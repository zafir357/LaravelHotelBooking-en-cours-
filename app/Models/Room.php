<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    /** @use HasFactory<\Database\Factories\RoomFactory> */
    use HasFactory;

    // Même bug que sur Booking : sans $fillable, Room::create($data) (utilisé
    // par RoomRepository::create(), donc StoreRoomRequest/RoomController::store())
    // levait une MassAssignmentException — la création de chambre par un admin
    // était cassée en pratique.
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'description',
        'price_per_night',
        'capacity',
        'is_available',
        'images',
    ];

    protected $casts = [
    'images' => 'array',
    'is_available' => 'boolean',
    'price_per_night' => 'decimal:2',
];

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
