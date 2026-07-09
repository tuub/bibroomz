<?php

declare(strict_types=1);

use App\Http\Controllers\HappeningController;
use App\Http\Requests\AddHappeningRequest;
use App\Http\Requests\CalendarEntriesRequest;
use App\Http\Requests\DeleteHappeningRequest;
use App\Http\Requests\UpdateHappeningRequest;
use App\Http\Requests\VerifyHappeningRequest;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\CreateHappeningAction;
use App\Services\Happenings\DeleteHappeningAction;
use App\Services\Happenings\ListCalendarEntriesAction;
use App\Services\Happenings\UpdateHappeningAction;
use App\Services\Happenings\VerifyHappeningAction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(HappeningController::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

afterEach(function (): void {
    Auth::logout();
});

test('HappeningController can be resolved from container', function (): void {
    $controller = app(HappeningController::class);

    expect($controller)->toBeInstanceOf(HappeningController::class);
});

test('addHappening aborts with 401 when user is not authenticated', function (): void {
    Auth::logout();

    $request = Mockery::mock(AddHappeningRequest::class);

    $controller = new HappeningController(
        Mockery::mock(ListCalendarEntriesAction::class),
        Mockery::mock(CreateHappeningAction::class),
        Mockery::mock(UpdateHappeningAction::class),
        Mockery::mock(VerifyHappeningAction::class),
        Mockery::mock(DeleteHappeningAction::class),
    );

    try {
        $controller->addHappening($request);
        test()->fail('Expected 401 abort');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(401);
    }
});

test('updateHappening aborts with 401 when user is not authenticated', function (): void {
    Auth::logout();

    $request = Mockery::mock(UpdateHappeningRequest::class);

    $controller = new HappeningController(
        Mockery::mock(ListCalendarEntriesAction::class),
        Mockery::mock(CreateHappeningAction::class),
        Mockery::mock(UpdateHappeningAction::class),
        Mockery::mock(VerifyHappeningAction::class),
        Mockery::mock(DeleteHappeningAction::class),
    );

    try {
        $controller->updateHappening($request);
        test()->fail('Expected 401 abort');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(401);
    }
});

test('verifyHappening aborts with 401 when user is not authenticated', function (): void {
    Auth::logout();

    $request = Mockery::mock(VerifyHappeningRequest::class);

    $controller = new HappeningController(
        Mockery::mock(ListCalendarEntriesAction::class),
        Mockery::mock(CreateHappeningAction::class),
        Mockery::mock(UpdateHappeningAction::class),
        Mockery::mock(VerifyHappeningAction::class),
        Mockery::mock(DeleteHappeningAction::class),
    );

    try {
        $controller->verifyHappening($request);
        test()->fail('Expected 401 abort');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(401);
    }
});

test('deleteHappening delegates to delete action', function (): void {
    $happening = new Happening;

    $request = Mockery::mock(DeleteHappeningRequest::class);
    $request->shouldReceive('happening')->once()->andReturn($happening);

    $deleteAction = Mockery::mock(DeleteHappeningAction::class);
    $deleteAction->shouldReceive('execute')->once()->with($happening);

    $controller = new HappeningController(
        Mockery::mock(ListCalendarEntriesAction::class),
        Mockery::mock(CreateHappeningAction::class),
        Mockery::mock(UpdateHappeningAction::class),
        Mockery::mock(VerifyHappeningAction::class),
        $deleteAction,
    );

    $controller->deleteHappening($request);
});

test('getHappenings passes null to list action when user is not authenticated', function (): void {
    // InstanceOfToTrue would make ($user instanceof User) always true, passing auth()->user() (null)
    // to the action instead of null. This test verifies the correct null is passed.
    Auth::logout();

    $resourceGroup = ResourceGroup::factory()->make();
    $start = CarbonImmutable::parse('2026-01-01 08:00:00');
    $end = CarbonImmutable::parse('2026-01-01 18:00:00');

    $request = Mockery::mock(CalendarEntriesRequest::class);
    $request->shouldReceive('resourceGroup')->once()->andReturn($resourceGroup);
    $request->shouldReceive('startAt')->once()->andReturn($start);
    $request->shouldReceive('endAt')->once()->andReturn($end);

    $listAction = Mockery::mock(ListCalendarEntriesAction::class);
    $listAction->shouldReceive('execute')
        ->once()
        ->withArgs(fn ($rg, $s, $e, $u): bool => $u === null)
        ->andReturn(collect());

    $controller = new HappeningController(
        $listAction,
        Mockery::mock(CreateHappeningAction::class),
        Mockery::mock(UpdateHappeningAction::class),
        Mockery::mock(VerifyHappeningAction::class),
        Mockery::mock(DeleteHappeningAction::class),
    );

    $response = $controller->getHappenings($request);
    expect($response->getStatusCode())->toBe(200);
});

test('getHappenings passes authenticated user to list action', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $resourceGroup = ResourceGroup::factory()->make();
    $start = CarbonImmutable::parse('2026-01-01 08:00:00');
    $end = CarbonImmutable::parse('2026-01-01 18:00:00');

    $request = Mockery::mock(CalendarEntriesRequest::class);
    $request->shouldReceive('resourceGroup')->once()->andReturn($resourceGroup);
    $request->shouldReceive('startAt')->once()->andReturn($start);
    $request->shouldReceive('endAt')->once()->andReturn($end);

    $listAction = Mockery::mock(ListCalendarEntriesAction::class);
    $listAction->shouldReceive('execute')
        ->once()
        ->withArgs(fn ($rg, $s, $e, $u): bool => $u instanceof User && $u->id === $user->id)
        ->andReturn(collect());

    $controller = new HappeningController(
        $listAction,
        Mockery::mock(CreateHappeningAction::class),
        Mockery::mock(UpdateHappeningAction::class),
        Mockery::mock(VerifyHappeningAction::class),
        Mockery::mock(DeleteHappeningAction::class),
    );

    $response = $controller->getHappenings($request);
    expect($response->getStatusCode())->toBe(200);
});

test('addHappening passes label and verifier to create action', function (): void {
    $user = User::factory()->create();
    $resource = new Resource;
    Auth::login($user);

    $start = CarbonImmutable::parse('2026-01-01 10:00:00');
    $end = CarbonImmutable::parse('2026-01-01 11:00:00');

    $request = Mockery::mock(AddHappeningRequest::class);
    $request->shouldReceive('input')->with('user_id_01')->andReturn(null);
    $request->shouldReceive('resource')->once()->andReturn($resource);
    $request->shouldReceive('startAt')->once()->andReturn($start);
    $request->shouldReceive('endAt')->once()->andReturn($end);
    $request->shouldReceive('label')->once()->andReturn(['en' => 'Work session']);
    $request->shouldReceive('verifier')->once()->andReturn('verifier@example.com');

    $createAction = Mockery::mock(CreateHappeningAction::class);
    $createAction->shouldReceive('executeForUser')
        ->once()
        ->withArgs(fn ($u, $r, $s, $e, $label, $verifier): bool => $label === ['en' => 'Work session'] && $verifier === 'verifier@example.com');

    $controller = new HappeningController(
        Mockery::mock(ListCalendarEntriesAction::class),
        $createAction,
        Mockery::mock(UpdateHappeningAction::class),
        Mockery::mock(VerifyHappeningAction::class),
        Mockery::mock(DeleteHappeningAction::class),
    );

    $response = $controller->addHappening($request);
    expect($response->getStatusCode())->toBe(204);
});

test('addHappening calls executeForAdmin when admin provides user_id_01', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create(['is_verification_required' => false]);
    $admin = User::factory()->create(['is_admin' => true]);
    $targetUser = User::factory()->create();
    Auth::login($admin);

    $start = CarbonImmutable::parse('2026-01-01 10:00:00');
    $end = CarbonImmutable::parse('2026-01-01 11:00:00');

    $request = Mockery::mock(AddHappeningRequest::class);
    $request->shouldReceive('input')->with('user_id_01')->andReturn($targetUser->id);
    $request->shouldReceive('resource')->once()->andReturn($resource);
    $request->shouldReceive('startAt')->once()->andReturn($start);
    $request->shouldReceive('endAt')->once()->andReturn($end);
    $request->shouldReceive('label')->once()->andReturn(null);

    $createAction = Mockery::mock(CreateHappeningAction::class);
    $createAction->shouldReceive('executeForAdmin')
        ->once()
        ->withArgs(fn (array $attrs): bool => $attrs['user_id_01'] === $targetUser->id
            && $attrs['resource_id'] === $resource->id
            && $attrs['is_verified'] === true
            && $attrs['verifier'] === null
        );

    $controller = new HappeningController(
        Mockery::mock(ListCalendarEntriesAction::class),
        $createAction,
        Mockery::mock(UpdateHappeningAction::class),
        Mockery::mock(VerifyHappeningAction::class),
        Mockery::mock(DeleteHappeningAction::class),
    );

    $response = $controller->addHappening($request);
    expect($response->getStatusCode())->toBe(204);
});

test('addHappening calls executeForUser when admin does not provide user_id_01', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $resource = new Resource;
    Auth::login($admin);

    $start = CarbonImmutable::parse('2026-01-01 10:00:00');
    $end = CarbonImmutable::parse('2026-01-01 11:00:00');

    $request = Mockery::mock(AddHappeningRequest::class);
    $request->shouldReceive('input')->with('user_id_01')->andReturn(null);
    $request->shouldReceive('resource')->once()->andReturn($resource);
    $request->shouldReceive('startAt')->once()->andReturn($start);
    $request->shouldReceive('endAt')->once()->andReturn($end);
    $request->shouldReceive('label')->once()->andReturn(null);
    $request->shouldReceive('verifier')->once()->andReturn(null);

    $createAction = Mockery::mock(CreateHappeningAction::class);
    $createAction->shouldReceive('executeForUser')
        ->once()
        ->withArgs(fn ($u): bool => $u->id === $admin->id);

    $controller = new HappeningController(
        Mockery::mock(ListCalendarEntriesAction::class),
        $createAction,
        Mockery::mock(UpdateHappeningAction::class),
        Mockery::mock(VerifyHappeningAction::class),
        Mockery::mock(DeleteHappeningAction::class),
    );

    $response = $controller->addHappening($request);
    expect($response->getStatusCode())->toBe(204);
});
