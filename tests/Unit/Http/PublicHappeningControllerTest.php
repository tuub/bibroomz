<?php

use App\Exceptions\HappeningValidationException;
use App\Http\Controllers\HappeningController;
use App\Http\Requests\AddHappeningRequest;
use App\Http\Requests\CalendarEntriesRequest;
use App\Http\Requests\DeleteHappeningRequest;
use App\Http\Requests\UpdateHappeningRequest;
use App\Http\Requests\VerifyHappeningRequest;
use App\Models\Happening;
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
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(
    HappeningController::class,
    AddHappeningRequest::class,
    CalendarEntriesRequest::class,
    DeleteHappeningRequest::class,
    UpdateHappeningRequest::class
);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

afterEach(function (): void {
    Auth::logout();
});

test('happening controller delegates calendar requests to the list action', function (): void {
    $resourceGroup = ResourceGroup::factory()->make();
    $start = CarbonImmutable::parse('2026-06-04 08:00:00');
    $end = CarbonImmutable::parse('2026-06-04 18:00:00');

    $request = Mockery::mock(CalendarEntriesRequest::class);
    $request->shouldReceive('resourceGroup')->once()->andReturn($resourceGroup);
    $request->shouldReceive('startAt')->once()->andReturn($start);
    $request->shouldReceive('endAt')->once()->andReturn($end);

    $listAction = Mockery::mock(ListCalendarEntriesAction::class);
    $listAction->shouldReceive('execute')
        ->once()
        ->with($resourceGroup, $start, $end, null)
        ->andReturn(collect([['id' => 'entry-1']]));

    $controller = new HappeningController(
        $listAction,
        Mockery::mock(CreateHappeningAction::class),
        Mockery::mock(UpdateHappeningAction::class),
        Mockery::mock(VerifyHappeningAction::class),
        Mockery::mock(DeleteHappeningAction::class),
    );

    $response = $controller->getHappenings($request);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true))->toBe([['id' => 'entry-1']]);
});

test('happening controller converts validation failures into 400 responses for create update and verify', function (): void {
    $user = User::factory()->create();
    $resource = new Resource;
    $happening = new Happening;
    $start = CarbonImmutable::parse('2026-06-04 10:00:00');
    $end = CarbonImmutable::parse('2026-06-04 11:00:00');

    Auth::login($user);

    $createRequest = Mockery::mock(AddHappeningRequest::class);
    $createRequest->shouldReceive('resource')->once()->andReturn($resource);
    $createRequest->shouldReceive('startAt')->once()->andReturn($start);
    $createRequest->shouldReceive('endAt')->once()->andReturn($end);
    $createRequest->shouldReceive('label')->once()->andReturn(['en' => 'Focus']);
    $createRequest->shouldReceive('verifier')->once()->andReturn('verifier.user');

    $updateRequest = Mockery::mock(UpdateHappeningRequest::class);
    $updateRequest->shouldReceive('happening')->once()->andReturn($happening);
    $updateRequest->shouldReceive('startAt')->once()->andReturn($start);
    $updateRequest->shouldReceive('endAt')->once()->andReturn($end);
    $updateRequest->shouldReceive('label')->once()->andReturn(['en' => 'Focus']);

    $verifyRequest = Mockery::mock(VerifyHappeningRequest::class);
    $verifyRequest->shouldReceive('happening')->once()->andReturn($happening);
    $verifyRequest->shouldReceive('startAt')->once()->andReturn($start);
    $verifyRequest->shouldReceive('endAt')->once()->andReturn($end);

    $createAction = Mockery::mock(CreateHappeningAction::class);
    $updateAction = Mockery::mock(UpdateHappeningAction::class);
    $verifyAction = Mockery::mock(VerifyHappeningAction::class);
    $deleteAction = Mockery::mock(DeleteHappeningAction::class);

    $createAction->shouldReceive('executeForUser')->once()->andThrow(
        new HappeningValidationException('auth.errors.user_not_found'),
    );
    $updateAction->shouldReceive('executeForUser')->once()->andThrow(
        new HappeningValidationException('auth.errors.user_not_found'),
    );
    $verifyAction->shouldReceive('execute')->once()->andThrow(
        new HappeningValidationException('auth.errors.user_not_found'),
    );

    $controller = new HappeningController(
        Mockery::mock(ListCalendarEntriesAction::class),
        $createAction,
        $updateAction,
        $verifyAction,
        $deleteAction,
    );

    foreach (
        [
            fn (): Response => $controller->addHappening($createRequest),
            fn (): Response => $controller->updateHappening($updateRequest),
            fn (): Response => $controller->verifyHappening($verifyRequest),
        ] as $callable
    ) {
        try {
            $callable();
            $this->fail('Expected the controller to abort with a 400 response.');
        } catch (HttpException $exception) {
            expect($exception->getStatusCode())->toBe(400)
                ->and($exception->getMessage())->toBe(__('auth.errors.user_not_found'));
        }
    }
});

test('happening controller delegates deletes to the delete action', function (): void {
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

    expect(true)->toBeTrue();
});
