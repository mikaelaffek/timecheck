<?php

namespace App\Listeners;

use App\Events\TimeRegistrationEvent;
use Illuminate\Support\Facades\Log;

class TimeRegistrationEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TimeRegistrationEvent $event): void
    {
        $context = [
            'action' => $event->action,
            'user_id' => $event->user?->id,
            'user_personal_id' => $event->user?->personal_id,
        ];

        if ($event->timeRegistration) {
            $context['time_registration_id'] = $event->timeRegistration->id;
            $context['date'] = $event->timeRegistration->date;
        }

        // Merge any additional data
        $context = array_merge($context, $event->data);

        // Log the event
        Log::info("TimeRegistration {$event->action}", $context);
    }
}
