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
        $settingable = Setting::getClosableModel($request->settingable_type)->find($request->settingable_id);

        $this->authorize('viewAny', [Setting::class, $settingable]);

        return Inertia::render('Admin/Settings/Index', [
            'settingable' => $settingable->withoutRelations(),
            'settingable_type' => $request->settingable_type,
            'settings' => $settingable->settings()->orderBy('key')->get(),
        ]);
    }

    public function editSetting(Request $request): Response
    {
        $setting = Setting::findOrFail($request->id);

        $this->authorize('edit', $setting);

        return Inertia::render('Admin/Settings/Form', [
            'setting' => $setting->only(['id', 'settingable_type', 'settingable_id', 'key', 'value'])
        ]);
    }

    public function updateSetting(UpdateSettingRequest $request): RedirectResponse
    {
        $setting = Setting::findOrFail($request->id);

        $this->authorize('edit', $setting);

        $validated = $request->validated();
        $setting->update($validated);

        return redirect()->route('admin.setting.index', [
            'settingable_type' => $setting->settingable_type,
            'settingable_id' => $setting->settingable_id,
        ]);
    }
}
