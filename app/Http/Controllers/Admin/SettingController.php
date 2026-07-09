<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SettingableContextRequest;
use App\Http\Requests\Admin\SettingKeyRequest;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\Setting;
use App\Services\Admin\SettingableResolver;
use App\Services\Admin\SettingAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends AdminController
{
    public function __construct(
        private readonly SettingAdminService $settingAdminService,
        private readonly SettingableResolver $settingableResolver,
    ) {}

    public function getSettings(SettingableContextRequest $request): Response
    {
        $settingable = $this->settingableResolver->resolve(
            $request->settingableType(),
            $request->settingableId(),
        );

        $this->authorize('viewAny', [Setting::class, $settingable]);

        return Inertia::render('Admin/Settings/Index', $this->settingAdminService->getIndexData(
            $settingable,
            $request->settingableType(),
        ));
    }

    public function editSetting(SettingKeyRequest $request): Response
    {
        $settingable = $this->settingableResolver->resolve(
            $request->settingableType(),
            $request->settingableId(),
        );

        $this->authorize('editAny', [Setting::class, $settingable]);

        return Inertia::render('Admin/Settings/Form', $this->settingAdminService->getEditFormData(
            $settingable,
            $request->settingableType(),
            $request->key(),
        ));
    }

    public function updateSetting(UpdateSettingRequest $request): RedirectResponse
    {
        $settingable = $this->settingableResolver->resolve(
            $request->settingableType(),
            $request->settingableId(),
        );

        $this->settingAdminService->update($settingable, $request->validated());

        return redirect()->route('admin.setting.index', [
            'settingable_id' => $request->settingableId(),
            'settingable_type' => $request->settingableType(),
        ]);
    }
}
