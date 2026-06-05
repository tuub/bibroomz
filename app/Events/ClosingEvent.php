<?php

namespace App\Events;

use App\Models\Closing;
use App\Models\Happening;
use App\Models\User;
use Illuminate\Support\Collection;

interface ClosingEvent
{
    public function user(): User;

    /**
     * @return Collection<int, Happening>
     */
    public function happenings(): Collection;

    public function closing(): Closing;
}
