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

    public function index(Request $request): AnonymousResourceCollection
    {
        if ($request->has(['check_in', 'check_out'])) {
            $rooms = $this->roomService->getAvailableRooms(
                $request->check_in,
                $request->check_out
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
