<?php

namespace App\Http\Controllers;

use App\Exceptions\HappeningValidationException;
use App\Http\Requests\AddHappeningRequest;
use App\Http\Requests\CalendarEntriesRequest;
use App\Http\Requests\DeleteHappeningRequest;
use App\Http\Requests\UpdateHappeningRequest;
use App\Http\Requests\VerifyHappeningRequest;
use App\Models\User;
use App\Services\Happenings\CreateHappeningAction;
use App\Services\Happenings\DeleteHappeningAction;
use App\Services\Happenings\ListCalendarEntriesAction;
use App\Services\Happenings\UpdateHappeningAction;
use App\Services\Happenings\VerifyHappeningAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class HappeningController extends Controller
{
    public function __construct(
        private readonly ListCalendarEntriesAction $listCalendarEntriesAction,
        private readonly CreateHappeningAction $createHappeningAction,
        private readonly UpdateHappeningAction $updateHappeningAction,
        private readonly VerifyHappeningAction $verifyHappeningAction,
        private readonly DeleteHappeningAction $deleteHappeningAction,
    ) {}

    public function getHappenings(CalendarEntriesRequest $request): JsonResponse
    {
        $user = auth()->user();

        return response()->json($this->listCalendarEntriesAction->execute(
            $request->resourceGroup(),
            $request->startAt(),
            $request->endAt(),
            $user instanceof User ? $user : null,
        ));
    }

    public function addHappening(AddHappeningRequest $request): Response
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 401);

        try {
            $this->createHappeningAction->executeForUser(
                $user,
                $request->resource(),
                $request->startAt(),
                $request->endAt(),
                $request->label(),
                $request->verifier(),
            );
        } catch (HappeningValidationException $exception) {
            abort(400, $exception->translatedMessage());
        }

        return response()->noContent();
    }

    public function updateHappening(UpdateHappeningRequest $request): Response
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 401);

        try {
            $this->updateHappeningAction->executeForUser(
                $user,
                $request->happening(),
                $request->startAt(),
                $request->endAt(),
                $request->label(),
            );
        } catch (HappeningValidationException $exception) {
            abort(400, $exception->translatedMessage());
        }

        return response()->noContent();
    }

    public function verifyHappening(VerifyHappeningRequest $request): Response
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 401);

        try {
            $this->verifyHappeningAction->execute(
                $user,
                $request->happening(),
                $request->startAt(),
                $request->endAt(),
            );
        } catch (HappeningValidationException $exception) {
            abort(400, $exception->translatedMessage());
        }

        return response()->noContent();
    }

    public function deleteHappening(DeleteHappeningRequest $request): void
    {
        $this->deleteHappeningAction->execute($request->happening());
    }
}
