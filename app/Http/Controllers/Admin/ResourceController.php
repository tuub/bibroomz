<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreResourceRequest;
use App\Http\Requests\Admin\UpdateResourceRequest;
use App\Http\Requests\Admin\CloneResourceRequest;
use App\Models\BusinessHour;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\WeekDay;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResourceController extends Controller
{
    public function getResources(Request $request): Response
    {
        $resources = Resource::with(['resource_group', 'business_hours', 'business_hours.week_days', 'closings'])
            ->where('resource_group_id', $request->resource_group_id)
            ->orderBy('title')->get()
            ->filter->isViewableByUser(auth()->user());

        return Inertia::render('Admin/Resources/Index', [
            'resourceGroup' => ResourceGroup::findOrFail($request->resource_group_id),
            'resources' => $resources,
        ]);
    }

    public function createResource(Request $request): Response
    {
        return Inertia::render('Admin/Resources/Form', [
            'resourceGroup' => ResourceGroup::findOrFail($request->resource_group_id),
            'weekDays' => WeekDay::get(),
            'languages' => config('app.supported_locales'),
        ]);
    }

    public function storeResource(StoreResourceRequest $request): RedirectResponse
    {
        $validated = $request->safe();

        $resource = Resource::create($validated->except('business_hours'));
        $this->updateOrCreateBusinessHours($validated->business_hours, $resource);

        return redirect()->route('admin.resource.index', ['resource_group_id' => $resource->resource_group_id]);
    }

    public function editResource(Request $request): Response
    {
        $resource = Resource::where('id', $request->id)->with([
            'business_hours',
            'business_hours.week_days:id'
        ])->firstOrFail();

        $this->authorize('edit', $resource);

        return Inertia::render('Admin/Resources/Form', [
            'resourceGroup' => $resource->resource_group,
            'resource' => [
                ...$resource->only([
                    'id',
                    'resource_group_id',
                    'location_uri',
                    'capacity',
                    'is_active',
                    'is_verification_required',
                ]),
                'title' => $resource->getTranslations('title'),
                'location' => $resource->getTranslations('location'),
                'description' => $resource->getTranslations('description'),
                'business_hours' => $resource->business_hours->map(fn ($business_hour) => [
                    'id' => $business_hour->id,
                    'start' => Carbon::parse($business_hour->start)->format('H:i'),
                    'end' => Carbon::parse($business_hour->end)->format('H:i'),
                    'week_days' => $business_hour->week_days->map->id,
                ]),
            ],
            'weekDays' => WeekDay::get(),
            'languages' => config('app.supported_locales'),
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

        return redirect()->route('admin.resource.index', ['resource_group_id' => $resource->resource_group_id]);
    }

    public function deleteResource(Request $request): RedirectResponse
    {
        $resource = Resource::find($request->id);
        $this->authorize('delete', $resource);
        $resource->delete();

        return redirect()->route('admin.resource.index', ['resource_group_id' => $resource->resource_group_id]);
    }

    private function updateOrCreateBusinessHours($business_hours, $resource)
    {
        foreach ($business_hours as $business_hour) {
            $result = BusinessHour::updateOrCreate(['id' => $business_hour['id']], [
                'resource_id' => $resource->id,
                'start' => Carbon::parse($business_hour['start'])->isMidnight() ? '00:00' : $business_hour['start'],
                'end' => Carbon::parse($business_hour['end'])->isMidnight() ? '24:00' : $business_hour['end'],
            ]);

            $result?->week_days()->sync($business_hour['week_days']);
        }
    }

    public function cloneResource(CloneResourceRequest $request)
    {
        $resource_original = Resource::with('resource_group', 'closings', 'business_hours', 'business_hours.week_days')
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
