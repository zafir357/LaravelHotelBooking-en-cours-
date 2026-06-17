<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('/login', 'auth/Login')->name('login');
Route::inertia('/register', 'auth/Register')->name('register');
Route::inertia('/dashboard', 'Dashboard')->name('dashboard');

// Vérification email — vérifie manuellement sans session ni token
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {

    // 1. Trouve le user par son ID
    $user = User::findOrFail($id);

    // 2. Vérifie que le hash correspond à l'email de ce user
    if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        abort(403, 'Invalid verification link.');
    }

    // 3. Vérifie que la signature n'est pas expirée/falsifiée
    if (!$request->hasValidSignature()) {
        abort(403, 'Link expired or invalid.');
    }

    // 4. Marque email_verified_at = maintenant en base
    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    // 5. Redirige vers le dashboard
    return redirect('/dashboard');

})->name('verification.verify');

require __DIR__.'/settings.php';
