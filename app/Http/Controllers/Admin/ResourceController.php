<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreResourceRequest;
use App\Http\Requests\Admin\UpdateResourceRequest;
use App\Http\Requests\Admin\CloneResourceRequest;
use App\Models\BusinessHour;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\WeekDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResourceController extends Controller
{
    public function getResources(): Response
    {
        $resources = Resource::with(['institution', 'business_hours', 'business_hours.week_days', 'closings'])
            ->orderBy('institution_id')->orderBy('title')->get()
            ->filter->isViewableByUser(auth()->user());

        return Inertia::render('Admin/Resources/Index', [
            'resources' => $resources,
        ]);
    }

    public function getFormResources()
    {
        $resources = Resource::active()->get()->filter->isEditableByUser(auth()->user())
            ->map->only(['id', 'title', 'institution_id', 'is_verification_required']);

        return $resources->values();
    }

    public function createResource(): Response
    {
        $institutions = Institution::active()->orderBy('title')->without('closings')->get()
            ->filter->isUserAbleToCreateResource(auth()->user());

        return Inertia::render('Admin/Resources/Form', [
            'institutions' => $institutions,
            'weekDays' => WeekDay::get(),
        ]);
    }

    public function storeResource(StoreResourceRequest $request): RedirectResponse
    {
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

        $this->authorize('edit', $resource);

        $institutions = Institution::active()->orderBy('title')->without('closings')->get()
            ->filter->isUserAbleToCreateResource(auth()->user());

        return Inertia::render('Admin/Resources/Form', [
            'resource' => $resource,
            'institutions' => $institutions,
            'weekDays' => WeekDay::get(),
        ]);
    }

    public function updateResource(UpdateResourceRequest $request): RedirectResponse
    {
        $resource = Resource::find($request->id);
        $validated = $request->safe();
        $resource->update($validated->except('business_hours'));

        BusinessHour::where('resource_id', $resource->id)
            ->whereNotIn('id', array_column($validated->business_hours, 'id'))
            ->delete();

        $this->updateOrCreateBusinessHours($validated->business_hours, $resource);

        return redirect()->route('admin.resource.index');
    }

    public function deleteResource(Request $request): RedirectResponse
    {
        $resource = Resource::find($request->id);
        $this->authorize('delete', $resource);
        $resource->delete();

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

    public function cloneResource(CloneResourceRequest $request)
    {
        $resource_original = Resource::with('institution', 'closings', 'business_hours', 'business_hours.week_days')
            ->findOrFail($request->id);

        $resource_copy = $resource_original->replicate();

        $resource_copy->title = $resource_original->title . ' ' . trans('admin.general.label.clone');
        $resource_copy->is_active = false;

        $resource_copy->save();

        $resource_copy->closings()->createMany($resource_original->closings->toArray());

        $resource_original->business_hours->each(function ($business_hour_original) use ($resource_copy) {
            $business_hour_copy = $resource_copy->business_hours()->create($business_hour_original->toArray());
            $business_hour_copy->week_days()->sync($business_hour_original->week_days->pluck('id'));
        });

        return redirect()->route('admin.resource.edit', $resource_copy->id);
    }
}
