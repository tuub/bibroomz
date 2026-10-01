<?php

namespace App\Services\Http;

use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;
use App\Services\Resources\GenerateResourceTimeSlotsAction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;

class GetResourceTimeSlotsAction
{
    public function __construct(private readonly GenerateResourceTimeSlotsAction $generateResourceTimeSlotsAction) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(
        string $resourceId,
        ?string $happeningId,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        // Slot generation only ever covers the single calendar day of $start (see
        // GenerateResourceTimeSlotsAction::initTimePeriod), so only happenings overlapping
        // that day can affect the result.
        $windowStart = $start->startOfDay();
        $windowEnd = $windowStart->addDay();

        $resource = Resource::query()
            ->with([
                'business_hours.week_days',
                'resource_group.settings',
                'resource_group.institution.settings',
                'resource_group.institution.closings',
                'happenings' => fn (Relation $query): Relation => $query
                    ->where('start', '<=', $windowEnd)
                    ->where('end', '>=', $windowStart),
            ])
            ->findOrFail($resourceId);

        $happening = $happeningId ? Happening::find($happeningId) : null;
        $actor = auth()->user();

        $slots = $this->generateResourceTimeSlotsAction->execute(
            $resource,
            $actor instanceof User ? $actor : null,
            $start,
            $end,
            $happening,
        );

        return [
            'start' => $this->presentSlots($slots['start']),
            'end' => $this->presentSlots($slots['end']),
        ];
    }

    /**
     * The frontend posts the chosen slot straight back as the happening's start
     * or end, so it has to leave here the way every other datetime does: as
     * wall-clock time in the app timezone, with no zone on it. Serializing the
     * Carbon itself would append a UTC offset and move the booking by it.
     *
     * @param  list<array{time: CarbonImmutable, label: string, is_disabled: bool, is_selected: bool}>  $slots
     * @return list<array{time: string, label: string, is_disabled: bool, is_selected: bool}>
     */
    private function presentSlots(array $slots): array
    {
        return array_map(static fn (array $slot): array => [
            ...$slot,
            'time' => $slot['time']->format('Y-m-d H:i:s'),
        ], $slots);
    }
}
