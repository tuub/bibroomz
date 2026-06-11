<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ClosingController;
use App\Http\Requests\Admin\ClosableContextRequest;
use App\Http\Requests\Admin\ClosingIdRequest;
use App\Http\Requests\Admin\DeleteClosingRequest;
use App\Http\Requests\Admin\StoreClosingRequest;
use App\Http\Requests\Admin\UpdateClosingRequest;
use App\Models\Closing;
use App\Models\Institution;
use App\Models\User;
use App\Services\Admin\ClosingAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(ClosingController::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

test('ClosingController can be resolved from container', function (): void {
    $controller = app(ClosingController::class);

    expect($controller)->toBeInstanceOf(ClosingController::class);
});

test('getClosings renders Inertia response with index data', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(ClosingAdminService::class);
    $request = Mockery::mock(ClosableContextRequest::class);

    $request->shouldReceive('closableType')->atLeast()->once()->andReturn('institution');
    $request->shouldReceive('closableId')->atLeast()->once()->andReturn($institution->id);
    $service->shouldReceive('resolveClosable')
        ->once()
        ->with('institution', $institution->id)
        ->andReturn($institution);
    $service->shouldReceive('getIndexData')
        ->once()
        ->with($institution, 'institution')
        ->andReturn(['closable' => $institution, 'closable_type' => 'institution', 'closings' => collect()]);

    $response = (new ClosingController($service))->getClosings($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('createClosing renders Inertia response with create form data', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(ClosingAdminService::class);
    $request = Mockery::mock(ClosableContextRequest::class);

    $request->shouldReceive('closableType')->atLeast()->once()->andReturn('institution');
    $request->shouldReceive('closableId')->atLeast()->once()->andReturn($institution->id);
    $service->shouldReceive('resolveClosable')
        ->once()
        ->with('institution', $institution->id)
        ->andReturn($institution);
    $service->shouldReceive('getCreateFormData')
        ->once()
        ->with($institution, 'institution')
        ->andReturn(['closable' => $institution, 'closable_type' => 'institution', 'languages' => []]);

    $response = (new ClosingController($service))->createClosing($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('storeClosing aborts 404 when closable is null', function (): void {
    $service = Mockery::mock(ClosingAdminService::class);
    $request = Mockery::mock(StoreClosingRequest::class);

    $request->shouldReceive('closable')->once()->andReturn(null);

    try {
        (new ClosingController($service))->storeClosing($request);
        test()->fail('Expected 404 abort');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(404);
    }
});

test('storeClosing stores and redirects to closing index', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(ClosingAdminService::class);
    $request = Mockery::mock(StoreClosingRequest::class);
    $validated = ['description' => ['en' => 'Holiday']];

    $request->shouldReceive('closable')->once()->andReturn($institution);
    $request->shouldReceive('validated')->once()->andReturn($validated);
    $request->shouldReceive('closableId')->once()->andReturn($institution->id);
    $request->shouldReceive('closableType')->once()->andReturn('institution');
    $service->shouldReceive('store')->once()->with($institution, $validated)->andReturn(new Closing);

    $response = (new ClosingController($service))->storeClosing($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toContain('closings');
});

test('editClosing renders Inertia response with edit form data', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();
    $user = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(ClosingAdminService::class);
    $request = Mockery::mock(ClosingIdRequest::class);

    $request->shouldReceive('closing')->once()->andReturn($closing);
    $service->shouldReceive('getEditFormData')
        ->once()
        ->with($closing)
        ->andReturn(['closing' => $closing, 'closable' => $institution, 'closable_type' => 'institution', 'languages' => []]);

    $response = (new ClosingController($service))->editClosing($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('updateClosing updates and redirects to closing index', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(ClosingAdminService::class);
    $request = Mockery::mock(UpdateClosingRequest::class);
    $validated = ['description' => ['en' => 'Updated']];

    $request->shouldReceive('closing')->once()->andReturn($closing);
    $request->shouldReceive('validated')->once()->andReturn($validated);
    $request->shouldReceive('closableId')->once()->andReturn($institution->id);
    $request->shouldReceive('closableType')->once()->andReturn('institution');
    $service->shouldReceive('update')->once()->with($closing, $validated)->andReturn($closing);

    $response = (new ClosingController($service))->updateClosing($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toContain('closings');
});

test('deleteClosing deletes and redirects to closing index', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $service = Mockery::mock(ClosingAdminService::class);
    $request = Mockery::mock(DeleteClosingRequest::class);

    $request->shouldReceive('closing')->once()->andReturn($closing);
    $service->shouldReceive('redirectData')
        ->once()
        ->with($closing)
        ->andReturn(['closable_id' => $institution->id, 'closable_type' => 'institution']);
    $service->shouldReceive('delete')->once()->with($closing);

    $response = (new ClosingController($service))->deleteClosing($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toContain('closings');
});
