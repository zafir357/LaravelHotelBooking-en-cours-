<?php

namespace App\Observers;

use App\Models\Booking;

class BookingObserver
{
   public function created(Booking $booking): void
    {
        Log::info("Booking #{$booking->id} created for user #{$booking->user_id}");
    }

    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('status')) {
            Log::info("Booking #{$booking->id} status changed to {$booking->status}");
        }
    }

    public function deleted(Booking $booking): void
    {
        Log::info("Booking #{$booking->id} deleted");
    }
}
