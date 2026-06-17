<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomController extends Controller
{
    public function __construct(
        private RoomService $roomService,
    ) {}

    // Endpoint appelé par Welcome.vue (aperçu homepage) ET par Rooms.vue (page
    // avec filtres) — un seul endpoint suffit car le comportement s'adapte
    // automatiquement à la présence ou non de paramètres de filtre dans l'URL.
    public function index(Request $request): AnonymousResourceCollection
    {
        // hasAny() vérifie si AU MOINS UN de ces paramètres est présent dans
        // l'URL (ex: GET /api/rooms?type=suite). Welcome.vue appelle l'API sans
        // aucun de ces paramètres -> on tombe dans le "else" et on retourne tout.
        // Rooms.vue, lui, envoie toujours au moins un de ces champs (même vide)
        // dès que l'utilisateur touche un filtre -> on passe par filterRooms().
        if ($request->hasAny(['type', 'min_price', 'max_price', 'capacity', 'check_in', 'check_out'])) {
            // only() extrait UNIQUEMENT ces clés de la requête, en ignorant tout
            // le reste — par sécurité, on ne veut jamais transmettre des paramètres
            // arbitraires non prévus jusqu'à la requête SQL du Repository.
            $rooms = $this->roomService->filterRooms(
                $request->only(['type', 'min_price', 'max_price', 'capacity', 'check_in', 'check_out'])
            );
        } else {
            $rooms = $this->roomService->getAllRooms();
        }

        return RoomResource::collection($rooms);
    }

    public function store(StoreRoomRequest $request): RoomResource
    {
        $room = $this->roomService->createRoom($request->validated());
        return new RoomResource($room);
    }

    public function show(int $id): RoomResource
    {
        $room = $this->roomService->getAllRooms()->find($id);
        return new RoomResource($room);
    }

    public function update(StoreRoomRequest $request, int $id): RoomResource
    {
        $room = $this->roomService->updateRoom($id, $request->validated());
        return new RoomResource($room);
    }

    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        $room = Room::findOrFail($id);
        $this->authorize('delete', $room);

        $this->roomService->deleteRoom($id);
        return response()->json(['message' => 'Room deleted.']);
    }
}
