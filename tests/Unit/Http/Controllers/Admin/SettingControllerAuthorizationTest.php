<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\SettingController;
use App\Http\Requests\Admin\SettingableContextRequest;
use App\Http\Requests\Admin\SettingIdRequest;
use App\Models\Institution;
use App\Models\Setting;
use App\Services\Admin\SettingableResolver;
use App\Services\Admin\SettingAdminService;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(SettingController::class);

uses(MockeryPHPUnitIntegration::class);

test('getSettings enforces authorization when no user is authenticated', function (): void {
    $settingable = new Institution;

    $resolver = Mockery::mock(SettingableResolver::class);
    $resolver->shouldReceive('resolve')->andReturn($settingable);

    $request = Mockery::mock(SettingableContextRequest::class);
    $request->shouldReceive('settingableType')->andReturn('institution');
    $request->shouldReceive('settingableId')->andReturn(1);

    $controller = new SettingController(Mockery::mock(SettingAdminService::class), $resolver);

    expect(fn (): Response => $controller->getSettings($request))->toThrow(AuthorizationException::class);
});

test('editSetting enforces authorization when no user is authenticated', function (): void {
    $setting = new Setting;

    $request = Mockery::mock(SettingIdRequest::class);
    $request->shouldReceive('setting')->andReturn($setting);

    $controller = new SettingController(
        Mockery::mock(SettingAdminService::class),
        Mockery::mock(SettingableResolver::class),
    );

    expect(fn (): Response => $controller->editSetting($request))->toThrow(AuthorizationException::class);
});
