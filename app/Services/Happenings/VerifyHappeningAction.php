<?php

namespace App\Services\Happenings;

use App\Events\HappeningVerifiedEvent;
use App\Models\Happening;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

class VerifyHappeningAction
{
    public function __construct(
        private readonly ValidateHappeningReservation $validator,
        private readonly HappeningBroadcaster $broadcaster,
    ) {}

    public function execute(
        User $user,
        Happening $happening,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): Happening {
        $this->validator->execute($user, $happening->resource, $start, $end, $happening);

        $happening->update([
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'verified_at' => Carbon::now(),
            'is_verified' => true,
            'verifier' => null,
            'user_id_02' => $user->getKey(),
        ]);

        $happening = $happening->withoutRelations()->refresh();

        $this->broadcaster->broadcast($happening, HappeningVerifiedEvent::class);

        return $happening;
    }
}
