<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePaymentIntentRequest;
use App\Models\Room;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    // Étape 1 du flow de paiement : avant même de créer une réservation, on
    // crée un PaymentIntent Stripe pour le montant exact de cette chambre sur
    // ces dates. Le frontend utilise le client_secret retourné pour afficher
    // le formulaire de carte (Stripe Elements) et collecter le paiement.
    public function store(CreatePaymentIntentRequest $request): JsonResponse
    {
        $room = Room::findOrFail((int) $request->validated()['room_id']);

        $intent = $this->paymentService->createIntent(
            $room,
            $request->validated()['check_in'],
            $request->validated()['check_out'],
        );

        return response()->json($intent);
    }
}
