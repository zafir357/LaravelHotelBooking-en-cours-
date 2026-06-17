<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Booking;
use App\Models\User;
class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Booking::class);

        // Admin et receptionist voient tout, guest voit les siennes
        if (auth()->user()->isGuest()) {
            $bookings = $this->bookingService->getUserBookings(auth()->id());
        } else {
            $bookings = $this->bookingService->getAllBookings();
        }

        return BookingResource::collection($bookings);
    }

    public function store(StoreBookingRequest $request): BookingResource
    {
        $this->authorize('create', Booking::class);

        $booking = $this->bookingService->createBooking([
            ...$request->validated(),
            'user_id' => auth()->id(),
        ]);

        return new BookingResource($booking);
    }

    public function update(UpdateBookingRequest $request, int $id): BookingResource
    {
        $booking = $this->bookingService->findById($id);
        $this->authorize('update', $booking);

        $booking = $this->bookingService->updateBooking($id, $request->validated());
        return new BookingResource($booking);
    }

    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        $booking = $this->bookingService->findById($id);
        $this->authorize('delete', $booking);

        $this->bookingService->cancelBooking($id);
        return response()->json(['message' => 'Booking cancelled.']);
    }
}
