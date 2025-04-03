<?php

namespace App\Http\Controllers;

use App\Events\HappeningVerifiedEvent;
use App\Events\HappeningCreatedEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\HappeningUpdatedEvent;
use App\Http\Requests\AddHappeningRequest;
use App\Http\Requests\DeleteHappeningRequest;
use App\Http\Requests\UpdateHappeningRequest;
use App\Http\Requests\VerifyHappeningRequest;
use App\Library\Utility;
use App\Models\Happening;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HappeningController extends Controller
{
    public function getHappenings(Request $request): JsonResponse
    {
        $resource_group = ResourceGroup::whereHas(
            'institution',
            fn ($query) => $query->where('slug', $request->institution_slug),
        )->where('slug', $request->resource_group_slug)->firstOrFail();

        $start = Carbon::parse($request->start);
        $end = Carbon::parse($request->end);

        return response()->json(
            collect()
                ->merge($this->getHappeningsOutputCollection($resource_group, $start, $end))
                ->merge($this->getInstitutionClosingsOutputCollection($resource_group, $start, $end))
                ->merge($this->getResourceClosingsOutputCollection($resource_group, $start, $end))
        );
    }

    private function getHappeningsOutputCollection(
        ResourceGroup $resource_group,
        Carbon $start,
        Carbon $end,
    ): Collection {
        $happenings = Happening::with('resource', 'user1', 'user2')
            ->resourceGroup($resource_group)
            ->whereDate('start', '>=', $start)
            ->whereDate('end', '<=', $end)
            ->active()
            ->get()
            ->filter->isResourceOpen()
            ->map->withAdjustedStartEndTimes();

        return $happenings->map(function ($happening) {
            $status = $happening->getStatus();

            return [
                'id' => $happening->id,
                'status' => $status,
                'resourceId' => $happening->resource->id,
                'start' => Carbon::parse($happening->start)->format('Y-m-d H:i'),
                'end' => Carbon::parse($happening->end)->format('Y-m-d H:i'),
                'classNames' => $status['type'],
                'can' => $happening->getPermissions(auth()->user()),
                'isVerificationRequired' => $happening->resource->is_verification_required,
                'resource' => [
                    'resourceGroup' => $happening->resource->resource_group->getTranslations('term_singular'),
                    'institution' => $happening->resource->resource_group->institution->title,
                ],
                'label' => $happening->getTranslations('label'),
            ];
        });
    }

    private function getInstitutionClosingsOutputCollection(
        ResourceGroup $resource_group,
        Carbon $start,
        Carbon $end,
    ): Collection {
        $closings = $resource_group->institution->closings;
        $resources = $resource_group->resources;

        return $closings
            ->filter(fn ($closing) => $closing->end->isAfter($start) && $closing->start->isBefore($end))
            ->flatMap(function ($closing) use ($resources) {
                return $resources->map(function ($resource) use ($closing) {
                    return [
                        'id' => $closing->id,
                        'status' => null,
                        'resourceId' => $resource->id,
                        'start' => Carbon::parse($closing->start)->format('Y-m-d H:i'),
                        'end' => Carbon::parse($closing->end)->format('Y-m-d H:i'),
                        'description' => $closing->getTranslations('description'),
                        'resource_group' => $resource->resource_group->getTranslations('term_singular'),
                        'user' => null,
                        'classNames' => 'closed',
                        'display' => 'background',
                    ];
                });
            });
    }

    private function getResourceClosingsOutputCollection(
        ResourceGroup $resource_group,
        Carbon $start,
        Carbon $end,
    ): Collection {
        $resources = $resource_group->resources;

        return $resources->flatMap(function ($resource) use ($start, $end) {
            return $resource->closings
                ->filter(fn ($closing) => $closing->end->isAfter($start) && $closing->start->isBefore($end))
                ->map(function ($closing) use ($resource) {
                    return [
                        'id' => $closing->id,
                        'status' => null,
                        'resourceId' => $resource->id,
                        'start' => Carbon::parse($closing->start)->format('Y-m-d H:i'),
                        'end' => Carbon::parse($closing->end)->format('Y-m-d H:i'),
                        'description' => $closing->getTranslations('description'),
                        'user' => null,
                        'classNames' => 'closed',
                        'display' => 'background',
                    ];
                });
        });
    }

    public function addHappening(AddHappeningRequest $request)
    {
        /** @var User */
        $user = auth()->user();

        /** @var Resource */
        $resource = Resource::find($request['resource']['id']);

        $start = new CarbonImmutable($request['start']);
        $end = new CarbonImmutable($request['end']);

        $this->isHappeningValid($user, $resource, $start, $end);

        $is_admin = $user->hasPermission('no_verifier', $resource->resource_group->institution);
        $is_verified = !$resource->isVerificationRequired() || $is_admin;

        // Compile happening payload
        $happening_data = [
            'user_id_01' => auth()->user()->id,
            'resource_id' => $resource->id,
            'is_verification_required' => $resource->isVerificationRequired(),
            'is_verified' => $is_verified,
            'verifier' => !$is_verified ? Utility::normalizeLoginName($request['verifier']) : null,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'reserved_at' => Carbon::now(),
            'verified_at' => $is_admin ? Carbon::now() : null,
            'label' => $request['label'],
        ];

        // Create
        $happening = Happening::create($happening_data);

        $happening->broadcast(HappeningCreatedEvent::class);
    }

    public function updateHappening(UpdateHappeningRequest $request)
    {
        /** @var User */
        $user = auth()->user();

        /** @var Happening */
        $happening = Happening::findOrFail($request->id);

        $this->isHappeningValid(
            $user,
            $happening->resource,
            new CarbonImmutable($request->start),
            new CarbonImmutable($request->end),
            $happening,
        );

        // Compile happening payload
        $happening_data = [
            'start' => Utility::carbonize($request->start)->format('Y-m-d H:i:s'),
            'end' => Utility::carbonize($request->end)->format('Y-m-d H:i:s'),
            'label' => $request->label,
        ];

        // Update
        $happening->update($happening_data);

        // Refresh
        $happening = $happening->withoutRelations()->refresh();

        // Broadcast
        $happening->broadcast(HappeningUpdatedEvent::class);
    }

    public function verifyHappening(VerifyHappeningRequest $request)
    {
        /** @var User */
        $user = auth()->user();

        /** @var Happening */
        $happening = Happening::findOrFail($request->id);

        $this->isHappeningValid(
            $user,
            $happening->resource,
            new CarbonImmutable($request->start),
            new CarbonImmutable($request->end),
            $happening,
        );

        // Update
        $happening->update([
            'start' => Utility::carbonize($request->start)->format('Y-m-d H:i:s'),
            'end' => Utility::carbonize($request->end)->format('Y-m-d H:i:s'),
        ]);

        // Verify
        $happening->update([
            'verified_at' => Carbon::now(),
            'is_verified' => true,
            'verifier' => null,
            'user_id_02' => $user->getKey(),
        ]);

        // Refresh
        $happening = $happening->withoutRelations()->refresh();

        // Broadcast
        $happening->broadcast(HappeningVerifiedEvent::class);
    }

    public function deleteHappening(DeleteHappeningRequest $request): void
    {
        /** @var Happening */
        $happening = Happening::findOrFail($request->id);

        if (!$happening->delete()) {
            return;
        }

        $happening->broadcast(HappeningDeletedEvent::class);
    }

    private function isHappeningValid(
        User $user,
        Resource $resource,
        CarbonImmutable $start,
        CarbonImmutable $end,
        Happening $happening = null,
    ): void {

        // check if user is in allowed user group
        if (!$resource->resource_group->isAllowedUser($user)) {
            abort(400, __('happening.errors.not_allowed_user', [
                'resource_type' => $resource->resource_group->term_singular,
                'resource_title' => $resource->title,
            ]));
        }

        // check if resource is closed
        [$closed] = $resource->findClosed($start, $end);
        if ($closed) {
            abort(400, __('happening.errors.closing', [
                'resource_type' => $resource->resource_group->term_singular,
                'resource_title' => $resource->title,
            ]));
        }

        // check if resource is open
        [$open] = $resource->findOpen($start, $end);
        if (!$open) {
            abort(400, __('happening.errors.business_hours', [
                'resource_type' => $resource->resource_group->term_singular,
                'resource_title' => $resource->title,
            ]));
        }

        // check if something is already happening
        if ($resource->isHappening($start, $end, $happening)) {
            abort(400, __('happening.errors.reserved', [
                'resource_type' => $resource->resource_group->term_singular,
                'resource_title' => $resource->title,
            ]));
        }

        // check if user is exceeding quotas
        if ($resource->isExceedingQuotas($start, $end, $happening)) {
            abort(400, __('happening.errors.quotas'));
        }

        // check if user has concurrent happening
        if (
            !$user->can('edit', $resource->resource_group->institution)
            && $user->isHavingConcurrentHappening($start, $end, $happening)
        ) {
            abort(400, __('happening.errors.concurrent'));
        }
    }
}
