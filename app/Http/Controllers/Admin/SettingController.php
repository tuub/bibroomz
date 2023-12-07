<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Library\Utility;
use App\Models\Setting;
use App\Services\AdminLoggingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(private AdminLoggingService $adminLoggingService)
    {
    }

    public function getSettings(Request $request): Response
    {
        $settingable = Setting::getSettingableModel($request->settingable_type)
            ->findByUuid($request->settingable_id);

        $this->authorize('viewAny', [Setting::class, $settingable]);

        return Inertia::render('Admin/Settings/Index', [
            'settingable' => $settingable->withoutRelations(),
            'settingable_type' => $request->settingable_type,
            'settings' => $settingable->settings()->orderBy('key')->get(),
        ]);
    }

    public function editSetting(Request $request): Response
    {
        $setting = Setting::findByUuid($request->id);

        $this->authorize('edit', $setting);

        $settingable_class = explode('\\', $setting->settingable_type);
        $settingable_type = Utility::convertCamelCaseToSnakeCase(end($settingable_class));

        return Inertia::render('Admin/Settings/Form', [
            'setting' => $setting,
            'settingable' => $setting->settingable,
            'settingable_type' => $settingable_type,
        ]);
    }

    public function updateSetting(UpdateSettingRequest $request): RedirectResponse
    {
        $setting = Setting::findByUuid($request->id);

        $this->authorize('edit', $setting);

        $validated = $request->validated();
        $setting->update($validated);

        $this->adminLoggingService->log('updated', $setting);

        return redirect()->route('admin.setting.index', [
            'settingable_id' => $request->settingable_id,
            'settingable_type' => $request->settingable_type,
        ]);
    }
}
