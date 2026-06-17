<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;

// Route::inertia() est un raccourci : pas besoin d'un Controller qui fait juste
// "return Inertia::render(...)". Laravel rend directement le composant Vue indiqué
// (2e argument) quand l'URL (1er argument) est visitée. Le ->name(...) donne un nom
// interne à la route, utilisable ailleurs dans le code via route('home') par exemple,
// au lieu d'écrire l'URL en dur (utile si l'URL change un jour).
Route::inertia('/', 'Welcome')->name('home');

// Page "tous les rooms avec filtres" (type, prix, capacité, dates) — séparée de la
// page d'accueil car celle-ci n'affiche qu'un aperçu de 3 chambres. Le composant Vue
// "Rooms" va lui-même appeler /api/rooms en AJAX pour récupérer + filtrer les données,
// cette route ne fait que livrer la coquille HTML/Vue vide au départ.
Route::inertia('/rooms', 'Rooms')->name('rooms');

Route::inertia('/login', 'auth/Login')->name('login');
Route::inertia('/register', 'auth/Register')->name('register');

// Protégée indirectement : la page elle-même vérifie côté Vue si l'utilisateur est
// connecté (via useAuth()) et redirige sinon. Ce n'est pas un vrai middleware "auth"
// Laravel ici, donc un visiteur non connecté PEUT charger cette route, mais le contenu
// utile ne s'affichera pas sans session valide.
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
