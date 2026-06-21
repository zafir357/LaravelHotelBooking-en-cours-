<?php

use App\Models\User;
use App\Notifications\SetPasswordInvite;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('a receptionist can invite a new receptionist', function () {
    Notification::fake();

    $receptionist = User::factory()->create(['role' => 'receptionist']);

    $response = $this->actingAs($receptionist)->postJson('/api/staff/receptionists', [
        'name' => 'New Reception',
        'email' => 'new-reception@hotel.test',
    ]);

    $response->assertCreated();

    $invited = User::where('email', 'new-reception@hotel.test')->first();

    expect($invited)->not->toBeNull();
    expect($invited->role)->toBe('receptionist');
    // Le mot de passe généré est aléatoire et ne doit JAMAIS correspondre à
    // un mot de passe "devinable" — on vérifie juste qu'il existe, pas sa valeur.
    expect($invited->password)->not->toBeEmpty();

    Notification::assertSentTo($invited, SetPasswordInvite::class);
});

test('a guest cannot invite a new receptionist', function () {
    $guest = User::factory()->create(['role' => 'guest']);

    $response = $this->actingAs($guest)->postJson('/api/staff/receptionists', [
        'name' => 'New Reception',
        'email' => 'new-reception@hotel.test',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('users', ['email' => 'new-reception@hotel.test']);
});

test('the set-password link rejects an invalid signature', function () {
    $invited = User::factory()->create(['role' => 'receptionist']);

    // Pas de signature du tout dans l'URL -> le middleware "signed" doit
    // bloquer la requête, peu importe que l'id du user soit valide.
    $response = $this->get("/staff/set-password/{$invited->id}");

    $response->assertForbidden();
});

test('a valid set-password link lets the invited user choose a password and log in', function () {
    $invited = User::factory()->create(['role' => 'receptionist', 'email_verified_at' => null]);

    $url = URL::temporarySignedRoute(
        'staff.set-password',
        now()->addHours(48),
        ['user' => $invited->id],
    );

    // La page se charge bien avec une signature valide.
    $this->get($url)->assertOk();

    // Soumettre vers la MÊME url (même query string, donc même signature)
    // est ce qui permet au middleware "signed" de revalider la requête côté POST.
    $this->post($url, [
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ])->assertRedirect('/login');

    $invited->refresh();

    expect($invited->hasVerifiedEmail())->toBeTrue();

    $this->postJson('/login', [
        'email' => $invited->email,
        'password' => 'a-brand-new-password',
    ])->assertOk();
});
