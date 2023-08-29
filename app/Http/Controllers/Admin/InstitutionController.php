<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInstitutionRequest;
use App\Http\Requests\Admin\UpdateInstitutionRequest;
use App\Models\Institution;
use App\Models\Setting;
use App\Models\WeekDay;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionController extends Controller
{
    public function getInstitutions()
    {
        return Inertia::render('Admin/Institutions/Index', [
            'institutions' => Institution::with(['resources', 'closings'])->get()
                ->filter->isViewableByUser(auth()->user()),
        ]);
    }

    public  function getFormInstitutions()
    {
        return Institution::get(['id', 'title'])
            ->filter->isEditableByUser(auth()->user())
            ->values();
    }

    public function createInstitution()
    {
        $this->authorize('create', Institution::class);

        $days_of_week = WeekDay::get();

        return Inertia::render('Admin/Institutions/Form', [
            'days_of_week' => $days_of_week,
        ]);
    }

    public function storeInstitution(StoreInstitutionRequest $request)
    {
        $validated = $request->safe();
        $institution = Institution::create($validated->except('week_days'));

        $institution->week_days()->sync($validated->week_days);

        // Init settings
        $settings = Setting::getInitialValues();
        foreach ($settings as $key => $value) {
            $setting = new Setting([
                'key' => $key,
                'value' => $value,
            ]);
            $institution->settings()->save($setting);
        }

        return redirect()->route('admin.institution.index');
    }

    public function editInstitution(Request $request)
    {
        $institution = Institution::where('id', $request->id)->with('closings', 'week_days:id')->firstOrFail();

        $this->authorize('edit', $institution);

        return Inertia::render('Admin/Institutions/Form', [
            'institution' => $institution,
            'days_of_week' => WeekDay::get(),
        ]);
    }

    public function updateInstitution(UpdateInstitutionRequest $request)
    {
        $institution = Institution::findOrFail($request->id);

        $validated = $request->safe();

        $institution->update($validated->except('week_days'));
        $institution->week_days()->sync($validated->week_days);

        return redirect()->route('admin.institution.index');
    }

    public function deleteInstitution(Request $request)
    {
        $institution = Institution::findOrFail($request->id);
        $this->authorize('delete', $institution);
        $institution->delete();

        return redirect()->route('admin.institution.index');
    }
}
