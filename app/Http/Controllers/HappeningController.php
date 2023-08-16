<?php

namespace App\Http\Controllers;

use App\Events\HappeningVerified;
use App\Events\HappeningCreated;
use App\Events\HappeningDeleted;
use App\Events\HappeningUpdated;
use App\Http\Requests\AddHappeningRequest;
use App\Http\Requests\UpdateHappeningRequest;
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
        sleep(2);
        $output = [];
        $institution = Institution::where('slug', $request->slug)->first();

        if (!$institution) {
            abort(404);
        }

        $from = Carbon::parse($request->start);
        $to = Carbon::parse($request->end);

        $log['PAYLOAD'] = json_encode(['from' => $from, 'to' => $to]);

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

        $log['OUTPUT'] = $output;

        Utility::sendToLog('happenings', $log);

        return response()->json($output);
    }

    public function addHappening(AddHappeningRequest $request)
    {
        /** @var User */
        $user = auth()->user();

        // Authorize
        if ($user->cannot('create', Happening::class)) {
            abort(401, 'You are not allowed to create.');
        }

        /** @var Resource */
        $resource = Resource::find($request['resource']['id']);
        $log['RESOURCE'] = $resource['title'];

        $start = new CarbonImmutable($request['start']);
        $end = new CarbonImmutable($request['end']);

        $this->isHappeningValid($resource, $start, $end);

        $is_admin = $user->isAdmin() || $user->isInstitutionAdmin($resource->institution);
        $is_verified = !$resource->is_verification_required || $is_admin;

        // Compile happening payload
        $happeningData = [
            'user_id_01' => auth()->user()->id,
            // 'user_id_02' => $is_admin ? auth()->user()->id : null,
            'resource_id' => $resource->id,
            'is_verification_required' => $resource->is_verification_required,
            'is_verified' => $is_verified,
            'verifier' => !$is_verified ? $request['verifier'] : null,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'reserved_at' => Carbon::now(),
            'verified_at' => $is_admin ? Carbon::now() : null,
        ];
        $log['PAYLOAD'] = json_encode($happeningData);

        // Create happening & relation
        $happening = new Happening($happeningData);
        $op = $happening->save() && $happening->resource()->associate($resource);
        $log['RESULT'] = json_encode($happening);

        if ($op) {
            // Write log record
            Utility::sendToLog('happenings', $log);

            $happening->broadcast(HappeningCreated::class);
        }
    }

    public function updateHappening(UpdateHappeningRequest $request)
    {
        /** @var User */
        $user = auth()->user();

        /** @var Happening */
        $happening = $user->happenings()->findOrFail($request->id);
        $log['HAPPENING'] = $happening;

        // Authorize
        if ($user->cannot('update', $happening)) {
            abort(401, 'You are not allowed to update.');
        }

        $start = new CarbonImmutable($request->start);
        $end = new CarbonImmutable($request->end);

        $this->isHappeningValid($happening->resource, $start, $end, $happening);

        // Compile happening payload
        $happening_data = [
            'start' => Utility::carbonize($request->start)->format('Y-m-d H:i:s'),
            'end' => Utility::carbonize($request->end)->format('Y-m-d H:i:s'),
        ];
        $log['PAYLOAD'] = json_encode($happening_data);

        // Update happening & relation
        $op = $happening->update($happening_data) && $happening->resource()->associate($request->id);

        if ($op) {
            // Get just updated relation object for broadcasting back
            $happening = Happening::with('resource')->find($happening->id);
            $log['RESULT'] = json_encode($happening);

            // Write log record
            Utility::sendToLog('happenings', $log);

            $happening->broadcast(HappeningUpdated::class);
        }
    }

    public function verifyHappening($id): void
    {
        $happening = Happening::findOrFail($id);

        /** @var User */
        $user = auth()->user();

        if ($user->cannot('verify', $happening)) {
            abort(401, 'You are not allowed to verify.');
        }

        $start = new CarbonImmutable($happening->start);
        $end = new CarbonImmutable($happening->end);

        if ($happening->resource->isExceedingQuotas($start, $end, $happening)) {
            abort(400, 'Exceeding quotas.');
        }

        $happening->user_id_02 = $user->getKey();
        $happening->is_verified = true;
        $happening->verifier = null;

        if ($happening->saveOrFail()) {
            $log['RESULT'] = json_encode($happening);
            Utility::sendToLog('happenings', $log);

            $happening->broadcast(HappeningVerified::class);
        }
    }

    public function deleteHappening($id): void
    {
        /** @var User */
        $user = auth()->user();
        $happening = $user->happenings()->findOrFail($id);

        if ($user->cannot('delete', $happening)) {
            abort(401, 'You are not allowed to delete.');
        }

        if ($happening->delete()) {
            $happening->broadcast(HappeningDeleted::class);
        }
    }

    private function isHappeningValid(Resource $resource, CarbonImmutable $start, CarbonImmutable $end, Happening $happening = null): void
    {
        // check if resource is closed
        // FIXME: [$closed] ?
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
    }
}
