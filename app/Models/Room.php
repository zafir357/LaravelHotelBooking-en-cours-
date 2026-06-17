<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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

    // Centralisé ici car utilisé à 3 endroits qui doivent impérativement
    // tomber d'accord sur le même montant : la création du PaymentIntent
    // Stripe (avant paiement), la vérification serveur après paiement
    // (on ne fait jamais confiance à un montant envoyé par le client), et
    // BookingService::createBooking() (le total_price final enregistré).
    // Si ce calcul vivait dupliqué à 3 endroits, un futur changement de
    // formule oublié dans un seul endroit casserait la vérification de
    // paiement de façon silencieuse.
    public function calculateTotalPrice(string $checkIn, string $checkOut): float
    {
        $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));

        return (float) $this->price_per_night * $nights;
    }
}
