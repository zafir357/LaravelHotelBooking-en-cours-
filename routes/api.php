<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RoomController;

// Routes publiques
// /register et /login ont été retirées : elles dupliquaient les routes de
// session Fortify (POST /register, POST /login, routes/web.php implicite).
// Le frontend appelle maintenant directement ces routes Fortify — voir
// useAuth.ts. Idem pour /logout, géré aussi par Fortify (POST /logout).
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{id}', [RoomController::class, 'show']);


// Routes protégées — "auth:sanctum" accepte ici soit un token Bearer, soit
// (grâce au mode stateful activé dans bootstrap/app.php) la session créée
// par le login Fortify.
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    // Étape 1 du paiement : crée le PaymentIntent Stripe AVANT la réservation.
    Route::post('/payments/intent', [PaymentController::class, 'store']);

    Route::apiResource('bookings', BookingController::class);

    // Routes admin/receptionist — gérées via Policy dans le controller
    Route::apiResource('rooms', RoomController::class)->except(['index', 'show']);
});
