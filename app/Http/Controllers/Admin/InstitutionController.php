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
        /** @var User */
        $user = auth()->user();

        $institutions = Institution::with(['resources', 'closings'])->get()
            ->filter(fn ($institution) => $user->can('edit', $institution));

        return Inertia::render('Admin/Institutions/Index', [
            'institutions' => $institutions,
        ]);
    }

    public  function getFormInstitutions()
    {
        /** @var User */
        $user = auth()->user();

        $institutions = Institution::get(['id', 'title'])
            ->filter(fn ($institution) => $user->can('edit', $institution));

        return $institutions->values();
    }

    public function createInstitution()
    {
        Institution::abortIfUnauthorized(verb: 'create');

        $days_of_week = WeekDay::get();

        return Inertia::render('Admin/Institutions/Form', [
            'days_of_week' => $days_of_week,
        ]);
    }

    public function storeInstitution(StoreInstitutionRequest $request)
    {
        Institution::abortIfUnauthorized(verb: 'create');

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
        //$institution = Institution::where('id', $request->id)->with('closings', 'week_days:id')->firstOrFail();
        $institution = Institution::where('id', $request->id)->with('closings', 'week_days:id')->firstOrFail();
        Institution::abortIfUnauthorized($institution);

        $days_of_week = WeekDay::get();

        return Inertia::render('Admin/Institutions/Form', [
            'institution' => $institution,
            'days_of_week' => $days_of_week,
        ]);
    }

    public function updateInstitution(UpdateInstitutionRequest $request)
    {
        $institution = Institution::findOrFail($request->id);
        Institution::abortIfUnauthorized($institution);

        $validated = $request->safe();
        $institution->update($validated->except('week_days'));

        // Set active week days
        $institution->week_days()->sync($validated->week_days);

        return redirect()->route('admin.institution.index');
    }

    public function deleteInstitution(Request $request)
    {
        $institution = Institution::findOrFail($request->id);
        Institution::abortIfUnauthorized($institution, verb: 'delete');

        $institution->delete();

        return redirect()->route('admin.institution.index');
    }
}
