<?php

use Illuminate\Auth\Events\Verified;
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

// 'auth' = redirige vers /login si pas connecté. 'verified' = redirige vers
// l'écran de vérification d'email si l'email n'est pas confirmé (le modèle
// User implémente MustVerifiedEmail, donc ce middleware sait quoi vérifier).
// Avant ce fix, cette route n'avait AUCUN middleware : un visiteur non connecté
// recevait un 200 OK en visitant /dashboard (la protection n'existait que côté
// Vue, contournable en désactivant simplement le JavaScript du navigateur).
Route::inertia('/dashboard', 'Dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Vérification email — vérifie manuellement sans session ni token
Route::get('/email/verify/{id}/{hash}', function (Request $request, string $id, string $hash) {

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

    // 4. Marque email_verified_at = maintenant en base.
    //    markEmailAsVerified() (fournie par le trait MustVerifyEmail) ne fait
    //    QUE sauvegarder la colonne — contrairement à ce qu'on pourrait croire,
    //    elle ne déclenche PAS l'event Verified toute seule. Normalement c'est
    //    le contrôleur de vérification standard de Fortify qui s'en charge ;
    //    comme cette route le contourne entièrement (vérification "à la main"),
    //    on doit déclencher l'event nous-mêmes — et seulement la première fois,
    //    pas à chaque revisite du lien par un utilisateur déjà vérifié.
    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    // 5. Redirige vers le dashboard. Le "?verified=1" est la convention Laravel
    //    standard (utilisée par VerifyEmailController par défaut) — certaines
    //    pages Vue s'en servent pour afficher un message de confirmation.
    return redirect('/dashboard?verified=1');

})->name('verification.verify');

require __DIR__.'/settings.php';
