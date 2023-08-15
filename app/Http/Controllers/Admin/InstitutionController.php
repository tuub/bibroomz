<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInstitutionRequest;
use App\Http\Requests\Admin\UpdateInstitutionRequest;
use App\Models\Institution;
use App\Models\Setting;
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

        return Inertia::render('Admin/Institutions/Form');
    }

    public function storeInstitution(StoreInstitutionRequest $request)
    {
        Institution::abortIfUnauthorized(verb: 'create');

        $validated = $request->validated();
        $institution = Institution::create($validated);

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
        $institution = Institution::where('id', $request->id)->with('closings')->firstOrFail();
        Institution::abortIfUnauthorized($institution);

        return Inertia::render('Admin/Institutions/Form', $institution);
    }

    public function updateInstitution(UpdateInstitutionRequest $request)
    {
        $institution = Institution::findOrFail($request->id);
        Institution::abortIfUnauthorized($institution);

        $validated = $request->validated();
        $institution->update($validated);

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
