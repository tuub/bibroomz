<?php

namespace App\Services\Happenings;

use App\Events\HappeningUpdatedEvent;
use App\Models\Happening;
use App\Models\User;
use Carbon\CarbonImmutable;

class UpdateHappeningAction
{
    public function __construct(
        private ValidateHappeningReservation $validator,
        private HappeningBroadcaster $broadcaster,
    ) {
    }

    public function executeForUser(
        User $user,
        Happening $happening,
        CarbonImmutable $start,
        CarbonImmutable $end,
        mixed $label,
    ): Happening {
        $this->validator->execute($user, $happening->resource, $start, $end, $happening);

        return $this->updateAndBroadcast($happening, [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'label' => $label,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function executeForAdmin(Happening $happening, array $attributes): Happening
    {
        return $this->updateAndBroadcast($happening, $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function updateAndBroadcast(Happening $happening, array $attributes): Happening
    {
        $happening->update($attributes);
        $happening = $happening->withoutRelations()->refresh();

        $this->broadcaster->broadcast($happening, HappeningUpdatedEvent::class);

        return $happening;
    }
}
