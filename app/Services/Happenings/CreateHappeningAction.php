<?php

namespace App\Services\Happenings;

use App\Events\HappeningCreatedEvent;
use App\Library\Utility;
use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

class CreateHappeningAction
{
    public function __construct(
        private ValidateHappeningReservation $validator,
        private HappeningBroadcaster $broadcaster,
    ) {
    }

    public function executeForUser(
        User $user,
        Resource $resource,
        CarbonImmutable $start,
        CarbonImmutable $end,
        mixed $label,
        ?string $verifier,
    ): Happening {
        $this->validator->execute($user, $resource, $start, $end);

        $isAdmin = $user->hasPermission('no_verifier', $resource->resource_group->institution);
        $isVerified = !$resource->isVerificationRequired() || $isAdmin;

        $happening = Happening::create([
            'user_id_01' => $user->id,
            'resource_id' => $resource->id,
            'is_verification_required' => $resource->isVerificationRequired(),
            'is_verified' => $isVerified,
            'verifier' => !$isVerified && $verifier ? Utility::normalizeLoginName($verifier) : null,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'reserved_at' => Carbon::now(),
            'verified_at' => $isAdmin ? Carbon::now() : null,
            'label' => $label,
        ]);

        $this->broadcaster->broadcast($happening, HappeningCreatedEvent::class);

        return $happening;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function executeForAdmin(array $attributes): Happening
    {
        $happening = Happening::create(array_merge($attributes, [
            'reserved_at' => Carbon::now(),
        ]));

        $this->broadcaster->broadcast($happening, HappeningCreatedEvent::class);

        return $happening;
    }
}
