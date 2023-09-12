<?php

namespace App\Http\Controllers;

use App\Events\HappeningVerified;
use App\Events\HappeningCreated;
use App\Events\HappeningDeleted;
use App\Events\HappeningUpdated;
use App\Http\Requests\AddHappeningRequest;
use App\Http\Requests\DeleteHappeningRequest;
use App\Http\Requests\UpdateHappeningRequest;
use App\Http\Requests\VerifyHappeningRequest;
use App\Library\Utility;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HappeningController extends Controller
{
    public function getHappenings(Request $request): JsonResponse
    {
        $output = [];
        $institution = Institution::where('slug', $request->slug)->firstOrFail();

        $from = Carbon::parse($request->start);
        $to = Carbon::parse($request->end);

        // LOG
        $log['PAYLOAD'] = json_encode(['from' => $from, 'to' => $to], JSON_PRETTY_PRINT);

        $institution_resources = $institution->resources()->pluck('id')->all();

        $happenings = Happening::with('resource', 'user1', 'user2')
            ->whereDate('start', '>=', $from)
            ->whereDate('end', '<=', $to)
            ->whereIn('resource_id', $institution_resources)
            ->get()
            ->map(function ($happening) {
                $start = CarbonImmutable::parse($happening->start);
                $end = CarbonImmutable::parse($happening->end);

                [$open, $start, $end] = $happening->resource->findOpen($start, $end);
                [$closed, $start, $end] = $happening->resource->findClosed($start, $end);

                $happening->start = $start;
                $happening->end = $end;

                return ($open && !$closed) ? $happening : null;
            })->filter(fn ($h) => $h);

        // LOG
        $log['RESULT COUNT'] = $happenings->count();

        foreach ($happenings as $happening) {
            $status = $happening->getStatus();

            // Note: resourceId and classNames have to be camelCased for FullCalendar recognition!
            $output[] = [
                'id' => $happening->id,
                'status' => $status,
                'resourceId' => $happening->resource->id,
                'start' => Carbon::parse($happening->start)->format('Y-m-d H:i'),
                'end' => Carbon::parse($happening->end)->format('Y-m-d H:i'),
                'classNames' => $status['type'],
                'can' => $happening->getPermissions(auth()->user()),
                'isVerificationRequired' => $happening->resource->is_verification_required,
            ];
        }

        // Since FullCalendar does not support closings as entities we have to include them
        // as happenings (sic!). We do it here for now until we find a better solution or
        // do it in the frontend.
        $institution_closings = $institution->closings;
        $institution_resources = $institution->resources;

        foreach ($institution_closings as $closing) {
            if ($closing->end->isAfter($from) && $closing->start->isBefore($to)) {
                foreach ($institution_resources as $resource) {
                    $output[] = [
                        'id' => $closing->id,
                        'status' => NULL,
                        'resourceId' => $resource->id,
                        'start' => Carbon::parse($closing->start)->format('Y-m-d H:i'),
                        'end' => Carbon::parse($closing->end)->format('Y-m-d H:i'),
                        'description' => $closing->description,
                        'user' => NULL,
                        'classNames' => 'closed',
                        'display' => 'background',
                    ];
                }
            }
        }

        foreach ($institution_resources as $resource) {
            foreach ($resource->closings as $closing) {
                if ($closing->end->isAfter($from) && $closing->start->isBefore($to)) {
                    $output[] = [
                        'id' => $closing->id,
                        'status' => NULL,
                        'resourceId' => $resource->id,
                        'start' => Carbon::parse($closing->start)->format('Y-m-d H:i'),
                        'end' => Carbon::parse($closing->end)->format('Y-m-d H:i'),
                        'description' => $closing->description,
                        'user' => NULL,
                        'classNames' => 'closed',
                        'display' => 'background',
                    ];
                }
            }
        }

        // LOG
        $log['OUTPUT'] = json_encode($output, JSON_PRETTY_PRINT);

        // LOG
        Utility::sendToLog('happenings', $log);

        return response()->json($output);
    }

    public function addHappening(AddHappeningRequest $request)
    {
        /** @var User */
        $user = auth()->user();

        /** @var Resource */
        $resource = Resource::find($request['resource']['id']);

        // LOG
        $log['RESOURCE'] = json_encode($resource->only(['id', 'title']), JSON_PRETTY_PRINT);

        $start = new CarbonImmutable($request['start']);
        $end = new CarbonImmutable($request['end']);

        $this->isHappeningValid($user, $resource, $start, $end);

        $is_admin = $user->hasPermission('no_verifier', $resource->institution);
        $is_verified = !$resource->isVerificationRequired() || $is_admin;

        // Compile happening payload
        $happening_data = [
            'user_id_01' => auth()->user()->id,
            'resource_id' => $resource->id,
            'is_verification_required' => $resource->isVerificationRequired(),
            'is_verified' => $is_verified,
            'verifier' => !$is_verified ? $request['verifier'] : null,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'reserved_at' => Carbon::now(),
            'verified_at' => $is_admin ? Carbon::now() : null,
        ];

        // LOG
        $log['PAYLOAD'] = json_encode($happening_data, JSON_PRETTY_PRINT);

        // Create
        $happening = Happening::create($happening_data);

        // LOG
        $log['RESULT'] = json_encode($happening, JSON_PRETTY_PRINT);

        // LOG
        Utility::sendToLog('happenings', $log);

        $happening->broadcast(HappeningCreated::class);
    }

    public function updateHappening(UpdateHappeningRequest $request)
    {
        /** @var User */
        $user = auth()->user();

        /** @var Happening */
        $happening = Happening::findOrFail($request->id);

        // LOG
        $log['HAPPENING'] = json_encode($happening, JSON_PRETTY_PRINT);

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
        ];

        // LOG
        $log['PAYLOAD'] = json_encode($happening_data, JSON_PRETTY_PRINT);

        // Update
        $happening->update($happening_data);

        // Refresh
        $happening = $happening->withoutRelations()->refresh();

        // LOG
        $log['RESULT'] = json_encode($happening, JSON_PRETTY_PRINT);

        // LOG
        Utility::sendToLog('happenings', $log);

        // Broadcast
        $happening->broadcast(HappeningUpdated::class);
    }

    public function verifyHappening(VerifyHappeningRequest $request)
    {
        /** @var User */
        $user = auth()->user();

        /** @var Happening */
        $happening = Happening::findOrFail($request->id);

        // LOG
        $log['HAPPENING'] = json_encode($happening, JSON_PRETTY_PRINT);

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

        // LOG
        $log['RESULT'] = json_encode($happening, JSON_PRETTY_PRINT);

        // LOG
        Utility::sendToLog('happenings', $log);

        // Broadcast
        $happening->broadcast(HappeningVerified::class);
    }

    public function deleteHappening(DeleteHappeningRequest $request): void
    {
        /** @var Happening */
        $happening = Happening::findOrFail($request->id);

        if (!$happening->delete()) {
            return;
        }

        $happening->broadcast(HappeningDeleted::class);
    }

    private function isHappeningValid(
        User $user,
        Resource $resource,
        CarbonImmutable $start,
        CarbonImmutable $end,
        Happening $happening = null,
    ): void {
        // check if resource is closed
        [$closed] = $resource->findClosed($start, $end);
        if ($closed) {
            abort(400, 'Resource is closed.');
        }

        // check if resource is open
        [$open] = $resource->findOpen($start, $end);
        if (!$open) {
            abort(400, 'Resource is not open.');
        }

        // check if something is already happening
        if ($resource->isHappening($start, $end, $happening)) {
            abort(400, 'Something is already happening.');
        }

        // check if user is exceeding quotas
        if ($resource->isExceedingQuotas($start, $end, $happening)) {
            abort(400, 'Exceeding quotas.');
        }

        // check if user has concurrent happening
        if (
            !$user->can('edit', $resource->institution)
            && $user->isHavingConcurrentHappening($start, $end, $happening)
        ) {
            abort(400, 'You can only have one happening at the same time!');
        }
    }
}
