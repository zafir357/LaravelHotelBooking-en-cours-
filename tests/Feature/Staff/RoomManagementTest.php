<?php

use App\Models\Room;
use App\Models\User;

function roomPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ocean View Suite',
        'type' => 'suite',
        'description' => 'A lovely suite with a sea view.',
        'price_per_night' => 120,
        'capacity' => 2,
        'images' => [],
    ], $overrides);
}

test('a receptionist can create a room', function () {
    $receptionist = User::factory()->create(['role' => 'receptionist']);

    $response = $this->actingAs($receptionist)->postJson('/api/rooms', roomPayload());

    $response->assertCreated();
    $this->assertDatabaseHas('rooms', ['name' => 'Ocean View Suite']);
});

test('a guest cannot create a room', function () {
    $guest = User::factory()->create(['role' => 'guest']);

    $response = $this->actingAs($guest)->postJson('/api/rooms', roomPayload());

    $response->assertForbidden();
    $this->assertDatabaseMissing('rooms', ['name' => 'Ocean View Suite']);
});

test('a receptionist can update and delete a room', function () {
    $receptionist = User::factory()->create(['role' => 'receptionist']);
    $room = Room::create(roomPayload(['name' => 'Garden Room']));

    $this->actingAs($receptionist)
        ->putJson("/api/rooms/{$room->id}", roomPayload(['name' => 'Garden Room (renovated)']))
        ->assertOk();

    $this->assertDatabaseHas('rooms', ['id' => $room->id, 'name' => 'Garden Room (renovated)']);

    $this->actingAs($receptionist)
        ->deleteJson("/api/rooms/{$room->id}")
        ->assertOk();

    $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
});
