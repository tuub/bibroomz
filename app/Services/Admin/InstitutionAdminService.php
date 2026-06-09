<?php

namespace App\Services\Admin;

use App\Models\Institution;
use App\Models\Setting;
use App\Models\User;
use App\Models\WeekDay;
use App\Services\AdminLoggingService;

class InstitutionAdminService
{
    public function __construct(private readonly AdminLoggingService $adminLoggingService) {}

    /**
     * @return array<string, mixed>
     */
    public function getIndexData(): array
    {
        $user = auth()->user();
        $institutions = Institution::query()
            ->with(['closings', 'resource_groups'])
            ->withCount('resource_groups', 'resources')
            ->orderBy('order')
            ->get();

        if ($user instanceof User) {
            $institutions = $institutions
                ->filter(fn (Institution $institution): bool => $institution->isViewableByUser($user))
                ->values();
        }

        return [
            'institutions' => $institutions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCreateFormData(): array
    {
        return [
            'daysOfWeek' => WeekDay::query()->get(),
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditFormData(Institution $institution): array
    {
        return [
            'institution' => $institution,
            'daysOfWeek' => WeekDay::query()->get(),
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @param  array<int, array{id: string, order: int}>  $rows
     */
    public function reorder(array $rows): void
    {
        foreach ($rows as $row) {
            $institution = Institution::query()->findOrFail($row['id']);
            $institution->update([
                'order' => $row['order'],
            ]);

            $this->adminLoggingService->log('reordered institution', $institution);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int|string>  $weekDays
     */
    public function store(array $attributes, array $weekDays): Institution
    {
        $institution = Institution::create($attributes);
        $institution->week_days()->sync($weekDays);

        foreach (Setting::getInitialValues()['institution'] as $key => $value) {
            $institution->settings()->create([
                'key' => $key,
                'value' => $value,
            ]);
        }

        $this->adminLoggingService->log('created', $institution);

        return $institution;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int|string>  $weekDays
     */
    public function update(Institution $institution, array $attributes, array $weekDays): Institution
    {
        $institution->update($attributes);
        $institution->week_days()->sync($weekDays);

        $this->adminLoggingService->log('updated', $institution);

        return $institution;
    }

    public function delete(Institution $institution): void
    {
        $institution->delete();

        $this->adminLoggingService->log('deleted', $institution);
    }
}
