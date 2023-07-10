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
    public static function getSettings(Request $request): Response
    {
        $institution = Institution::findOrFail($request->id);
        return Inertia::render('Admin/Settings/Index', [
            'settings' => $institution->settings,
        ]);
    }

    public function editSetting(Request $request): Response
    {
        $setting = Setting::where('id', $request->id)->firstOrFail();
        return Inertia::render('Admin/Settings/Form', $setting);
    }

    public function updateSetting(UpdateSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $setting = Setting::find($request->id);
        $setting->update($validated);

        return redirect()->route('admin.setting.index');
    }
}
