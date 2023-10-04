<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\Institution;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function getSettings(Request $request): Response
    {
        $institution = Institution::with('settings')->findOrFail($request->institution_id);

        $this->authorize('viewAny', [Setting::class, $institution]);

        return Inertia::render('Admin/Settings/Index', [
            'institution' => $institution,
        ]);
    }

    public function editSetting(Request $request): Response
    {
        $setting = Setting::findOrFail($request->id);

        $this->authorize('edit', $setting);

        return Inertia::render('Admin/Settings/Form', [
            'setting' => $setting->only(['id', 'key', 'value'])
        ]);
    }

    public function updateSetting(UpdateSettingRequest $request): RedirectResponse
    {
        $setting = Setting::findOrFail($request->id);

        $this->authorize('edit', $setting);

        $validated = $request->validated();
        $setting->update($validated);

        return redirect()->route('admin.setting.index', [
            'institution_id' => $setting->institution_id,
        ]);
    }
}
