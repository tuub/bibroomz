<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreResourceRequest;
use App\Http\Requests\Admin\UpdateResourceRequest;
use App\Models\BusinessHour;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\User;
use App\Models\WeekDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResourceController extends Controller
{
    public static function getResources(): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $resources = Resource::with(['institution', 'business_hours', 'business_hours.week_days', 'closings'])->get()
            ->filter(fn ($resource) => $user->can('edit', $resource->institution));

        return Inertia::render('Admin/Resources/Index', ['resources' => $resources]);
    }

    public static function getFormResources()
    {
        /** @var User $user */
        $user = auth()->user();

        $resources = Resource::active()->get()
            ->filter(fn ($resource) => $user->can('edit', $resource->institution))
            ->map->only(['id', 'title', 'institution_id', 'is_verification_required']);

        return $resources->values();
    }

    public function deleteResource(Request $request): RedirectResponse
    {
        $resource = Resource::find($request->id);
        Institution::abortIfUnauthorized($resource->institution);

        $resource->delete();

        return redirect()->route('admin.resource.index');
    }

    public function createResource(): Response
    {
        $week_days = WeekDay::get();

        return Inertia::render('Admin/Resources/Form', [
            'week_days' => $week_days,
        ]);
    }

    public function storeResource(StoreResourceRequest $request): RedirectResponse
    {
        $institution = Institution::findOrFail($request->institution_id);
        Institution::abortIfUnauthorized($institution);

        $validated = $request->safe();
        $resource = Resource::create($validated->except('business_hours'));

        $this->updateOrCreateBusinessHours($validated->business_hours, $resource);

        return redirect()->route('admin.resource.index');
    }

    public function editResource(Request $request): Response
    {
        $resource = Resource::where('id', $request->id)->with([
            'closings',
            'business_hours',
            'business_hours.week_days:id'
        ])->firstOrFail();

        Institution::abortIfUnauthorized($resource->institution);

        $week_days = WeekDay::get();

        return Inertia::render('Admin/Resources/Form', [
            'resource' => $resource,
            'week_days' => $week_days,
        ]);
    }

    public function updateResource(UpdateResourceRequest $request): RedirectResponse
    {
        // Check both old and new institution for authorization
        $resource = Resource::find($request->id);
        Institution::abortIfUnauthorized($resource->institution);

        $institution = Institution::findOrFail($request->institution_id);
        Institution::abortIfUnauthorized($institution);

        $validated = $request->safe();
        $resource->update($validated->except('business_hours'));

        BusinessHour::where('resource_id', $resource->id)
            ->whereNotIn('id', array_column($validated->business_hours, 'id'))
            ->delete();

        $this->updateOrCreateBusinessHours($validated->business_hours, $resource);

        return redirect()->route('admin.resource.index');
    }

    private function updateOrCreateBusinessHours($business_hours, $resource)
    {
        foreach ($business_hours as $business_hour) {
            $result = BusinessHour::updateOrCreate(['id' => $business_hour['id']], [
                'resource_id' => $resource->id,
                'start' => $business_hour['start'],
                'end' => $business_hour['end'],
            ]);

            $result?->week_days()->sync($business_hour['week_days']);
        }
    }

    public function cloneResource(Request $request)
    {
        $resource_original = Resource::with('institution', 'closings', 'business_hours', 'business_hours.week_days')
            ->findOrFail($request->id);
        Institution::abortIfUnauthorized($resource_original->institution);

        $resource_copy = $resource_original->replicate();

        $resource_copy->title = $resource_original->title . ' ' . trans('admin.general.label.clone');
        $resource_copy->is_active = false;

        $resource_copy->save();

        $resource_copy->closings()->createMany($resource_original->closings->toArray());

        $resource_original->business_hours->each(function ($business_hour_original) use ($resource_copy) {
            $business_hour_copy = $resource_copy->business_hours()->create($business_hour_original->toArray());
            $business_hour_copy->week_days()->sync($business_hour_original->week_days->pluck('id'));
        });

        $week_days = WeekDay::get();

        return Inertia::render('Admin/Resources/Form', [
            'resource' => $resource_copy,
            'week_days' => $week_days,
        ]);
    }
}
