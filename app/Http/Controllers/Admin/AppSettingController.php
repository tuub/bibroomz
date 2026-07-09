<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\UpdateAppSettingRequest;
use App\Models\AppSetting;
use App\Services\AdminLoggingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppSettingController extends AdminController
{
    public function __construct(private readonly AdminLoggingService $adminLoggingService) {}

    public function index(): Response
    {
        $this->authorize('admin');

        $settings = [];
        foreach (AppSetting::getCurrentValues() as $key => $value) {
            $settings[] = ['key' => $key, 'value' => $value];
        }

        return Inertia::render('Admin/AppSettings/Index', [
            'settings' => $settings,
        ]);
    }

    public function edit(): Response
    {
        $this->authorize('admin');

        $inputTypes = [];
        foreach (AppSetting::getDefinitionKeys() as $key) {
            $inputTypes[$key] = AppSetting::getInputType($key);
        }

        return Inertia::render('Admin/AppSettings/Edit', [
            'settings' => AppSetting::getCurrentValues(),
            'inputTypes' => $inputTypes,
        ]);
    }

    public function update(UpdateAppSettingRequest $request): RedirectResponse
    {
        foreach (AppSetting::getDefinitionKeys() as $key) {
            $appSetting = AppSetting::query()->find($key);
            $action = $appSetting instanceof AppSetting ? 'updated' : 'created';

            $appSetting = AppSetting::set($key, $request->validated($key));

            $this->adminLoggingService->log($action, $appSetting);
        }

        return redirect()->route('admin.app_setting.index');
    }
}
