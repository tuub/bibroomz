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
    public static function getInstitutions(Request $request)
    {
        return Inertia::render('Admin/Institutions/Index', [
            'institutions' => Institution::with(['resources', 'closings'])->get()
        ]);
    }

    public static function getFormInstitutions()
    {
        return Institution::get(['id', 'title']);
    }

    public function createInstitution(Request $request)
    {
        return Inertia::render('Admin/Institutions/Form');
    }

    public function storeInstitution(StoreInstitutionRequest $request)
    {
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
        return Inertia::render('Admin/Institutions/Form', $institution);
    }

    public function updateInstitution(UpdateInstitutionRequest $request)
    {
        $resource = Institution::findOrFail($request->id);

        $validated = $request->validated();
        $resource->update($validated);

        return redirect()->route('admin.institution.index');
    }

    public function deleteInstitution(Request $request)
    {
        $institutions = Institution::find($request->id);
        $institutions->delete();

        return redirect()->route('admin.institution.index');
    }
}
