<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\SendConfirmationEmail;
class SendBookingConfirmation
{
    public function handle(BookingCreated $event): void
    {
        SendConfirmationEmail::dispatch($event->booking);
    }
}
