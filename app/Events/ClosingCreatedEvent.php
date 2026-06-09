<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Closing;
use App\Models\Happening;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ClosingCreatedEvent implements ClosingEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Collection<int, Happening>  $happenings
     */
    public function __construct(
        public User $user,
        public Collection $happenings,
        public Closing $closing,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function happenings(): Collection
    {
        return $this->happenings;
    }

    public function closing(): Closing
    {
        return $this->closing;
    }
}
