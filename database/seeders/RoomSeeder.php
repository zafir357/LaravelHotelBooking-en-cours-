<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        Room::create([
            'name' => 'Chambre Standard 101',
            'type' => 'single',
            'description' => 'Chambre confortable avec vue sur jardin.',
            'price_per_night' => 79.00,
            'capacity' => 1,
            'is_available' => true,
            'images' => [Storage::disk('public')->url('rooms/standard-101.jpg')],
        ]);

        Room::create([
            'name' => 'Chambre Double 202',
            'type' => 'double',
            'description' => 'Chambre spacieuse avec lit double.',
            'price_per_night' => 119.00,
            'capacity' => 2,
            'is_available' => true,
            'images' => [Storage::disk('public')->url('rooms/double-202.jpg')],
        ]);

        Room::create([
            'name' => 'Suite Deluxe 301',
            'type' => 'suite',
            'description' => 'Suite avec balcon et vue sur mer.',
            'price_per_night' => 249.00,
            'capacity' => 4,
            'is_available' => true,
            'images' => [Storage::disk('public')->url('rooms/suite-301.jpg')],
        ]);
    }
}
