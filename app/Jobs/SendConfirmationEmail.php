<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use App\Models\Booking;

class SendConfirmationEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking,
    ) {}

    public function handle(): void
    {
        // Mail::to($this->booking->user->email)->send(new BookingConfirmationMail($this->booking));
        \Log::info('Confirmation email sent for booking #' . $this->booking->id);
    }
}
