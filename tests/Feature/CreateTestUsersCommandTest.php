<?php

use App\Models\User;

test('it creates a pre-verified guest and receptionist account', function () {
    $this->artisan('app:create-test-users')->assertSuccessful();

    $guest = User::where('email', 'test_guest@hotel.test')->first();
    $receptionist = User::where('email', 'test_receptionist@hotel.test')->first();

    expect($guest)->not->toBeNull();
    expect($guest->role)->toBe('guest');
    expect($guest->hasVerifiedEmail())->toBeTrue();

    expect($receptionist)->not->toBeNull();
    expect($receptionist->role)->toBe('receptionist');
    expect($receptionist->hasVerifiedEmail())->toBeTrue();

    // Mots de passe connus à l'avance ("password") pour pouvoir se
    // connecter directement, sans passer par un lien de vérification.
    $this->postJson('/login', ['email' => $guest->email, 'password' => 'password'])->assertOk();
});

test('running it twice does not fail (idempotent)', function () {
    $this->artisan('app:create-test-users')->assertSuccessful();
    $this->artisan('app:create-test-users')->assertSuccessful();

    expect(User::where('email', 'test_guest@hotel.test')->count())->toBe(1);
});
