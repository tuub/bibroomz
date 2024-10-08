<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClosingRequest;
use App\Http\Requests\Admin\UpdateClosingRequest;
use App\Library\Utility;
use App\Events\ClosingCreatedEvent;
use App\Events\ClosingDeletedEvent;
use App\Events\ClosingUpdatedEvent;
use App\Models\Closing;
use App\Services\AdminLoggingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class ClosingController extends Controller
{
    public function __construct(private AdminLoggingService $adminLoggingService)
    {
    }

    public function getClosings(Request $request)
    {
        $closable = Closing::getClosableModel($request->closable_type)
            ->find($request->closable_id);

        $this->authorize('viewAny', [Closing::class, $closable]);

        return Inertia::render('Admin/Closings/Index', [
            'closable' => $closable->withoutRelations(),
            'closable_type' => $request->closable_type,
            'closings' => $closable->closings()->orderBy('start')->get(),
        ]);
    }

    public function createClosing(Request $request): Response
    {
        $closable = Closing::getClosableModel($request->closable_type)
            ->find($request->closable_id);

        $this->authorize('create', [Closing::class, $closable]);

        return Inertia::render('Admin/Closings/Form', [
            'closable' => $closable,
            'closable_type' => $request->closable_type,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function storeClosing(StoreClosingRequest $request): RedirectResponse
    {
        $closable = Closing::getClosableModel($request->closable_type)
            ->find($request->closable_id);

        $this->authorize('create', [Closing::class, $closable]);

        $validated = $request->validated();
        $sanitized = self::sanitizeClosingData($validated);

        $closing = Closing::create($sanitized);

        if ($closable->closings()->save($closing)) {
            $users = $closing->getUsersAffected();

            foreach ($users as $user) {
                $happenings = $closing->getUserHappeningsAffected($user);
                ClosingCreatedEvent::dispatch($user, $happenings, $closing);
            }
        }

        $this->adminLoggingService->log('created', $closing);

        return redirect()->route('admin.closing.index', [
            'closable_id' => $request->closable_id,
            'closable_type' => $request->closable_type,
        ]);
    }

    public function editClosing(Request $request): Response
    {
        $closing = Closing::find($request->id);

        $this->authorize('edit', $closing);

        $closable_class = explode('\\', $closing->closable_type);
        $closable_type = Utility::convertCamelCaseToSnakeCase(end($closable_class));

        $closing->start_date = Carbon::parse($closing->start)->format('d.m.Y');
        $closing->start_time = Carbon::parse($closing->start)->format('H:i');
        $closing->end_date = Carbon::parse($closing->end)->format('d.m.Y');
        $closing->end_time = Carbon::parse($closing->end)->format('H:i');

        return Inertia::render('Admin/Closings/Form', [
            'closing' => $closing,
            'closable' => $closing->closable,
            'closable_type' => $closable_type,
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function updateClosing(UpdateClosingRequest $request): RedirectResponse
    {
        $closing = Closing::find($request->id);

        $this->authorize('edit', $closing);

        $validated = $request->validated();
        $sanitized = self::sanitizeClosingData($validated);

        if ($closing->update($sanitized)) {
            $users = $closing->getUsersAffected();

            foreach ($users as $user) {
                $happenings = $closing->getUserHappeningsAffected($user);
                ClosingUpdatedEvent::dispatch($user, $happenings, $closing);
            }
        }

        $this->adminLoggingService->log('updated', $closing);

        return redirect()->route('admin.closing.index', [
            'closable_id' => $request->closable_id,
            'closable_type' => $request->closable_type,
        ]);
    }

    public function deleteClosing(Request $request): RedirectResponse
    {
        $closing = Closing::find($request->id);

        $this->authorize('delete', $closing);

        if ($closing->delete()) {
            $users = $closing->getUsersAffected();

            foreach ($users as $user) {
                $happenings = $closing->getUserHappeningsAffected($user);
                ClosingDeletedEvent::dispatch($user, $happenings, $closing);
            }
        }

        $closable_type = explode('\\', $closing->closable_type);

        $this->adminLoggingService->log('deleted', $closing);

        return redirect()->route('admin.closing.index', [
            'closable_id' => $request->closable_id,
            'closable_type' => strtolower(end($closable_type)),
        ]);
    }

    public static function sanitizeClosingData($closing_data): array
    {
        $closing_data['start'] = Utility::createCarbonDateTime(
            $closing_data['start_date'],
            $closing_data['start_time']
        )->toISOString();
        $closing_data['end'] = Utility::createCarbonDateTime(
            $closing_data['end_date'],
            $closing_data['end_time']
        )->toIsoString();

        return Arr::except($closing_data, [
            'start_date',
            'start_time',
            'end_date',
            'end_time'
        ]);
    }
}
