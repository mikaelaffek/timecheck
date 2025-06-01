<?php

namespace App\Events;

use App\Models\TimeRegistration;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimeRegistrationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    public $action;

    /**
     * @var array
     */
    public $data;

    /**
     * @var User|null
     */
    public $user;

    /**
     * @var TimeRegistration|null
     */
    public $timeRegistration;

    /**
     * Create a new event instance.
     */
    public function __construct(string $action, array $data = [], ?User $user = null, ?TimeRegistration $timeRegistration = null)
    {
        $this->action = $action;
        $this->data = $data;
        $this->user = $user;
        $this->timeRegistration = $timeRegistration;
    }
}
