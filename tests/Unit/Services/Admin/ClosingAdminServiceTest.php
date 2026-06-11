<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Admin\ClosingAdminService;
use App\Services\AdminLoggingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(ClosingAdminService::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

test('resolveClosable returns institution for institution type', function (): void {
    $institution = Institution::factory()->create();

    $service = app(ClosingAdminService::class);
    $result = $service->resolveClosable('institution', $institution->id);

    expect($result)->toBeInstanceOf(Institution::class)
        ->and($result->id)->toBe($institution->id);
});

test('getIndexData returns closings key', function (): void {
    $institution = Institution::factory()->create();

    $service = app(ClosingAdminService::class);
    $data = $service->getIndexData($institution, 'institution');

    expect($data)->toHaveKey('closings');
});

test('delete removes closing', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();
    $id = $closing->id;

    $service = app(ClosingAdminService::class);
    $service->delete($closing);

    expect(Closing::find($id))->toBeNull();
});

test('getIndexData returns closable and closable_type keys', function (): void {
    $institution = Institution::factory()->create();
    $service = app(ClosingAdminService::class);

    $data = $service->getIndexData($institution, 'institution');

    expect($data)->toHaveKey('closable')
        ->and($data)->toHaveKey('closable_type')
        ->and($data['closable_type'])->toBe('institution');
});

test('getCreateFormData returns closable closable_type and languages keys', function (): void {
    $institution = Institution::factory()->create();
    $service = app(ClosingAdminService::class);

    $data = $service->getCreateFormData($institution, 'institution');

    expect($data)->toHaveKey('closable')
        ->and($data)->toHaveKey('closable_type')
        ->and($data)->toHaveKey('languages');
});

test('getEditFormData returns closing closable closable_type and languages keys', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();
    $service = app(ClosingAdminService::class);

    $data = $service->getEditFormData($closing);

    expect($data)->toHaveKey('closing')
        ->and($data)->toHaveKey('closable')
        ->and($data)->toHaveKey('closable_type')
        ->and($data)->toHaveKey('languages');
});

test('getEditFormData closing array contains formatted date fields', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create([
        'start' => Carbon::parse('2025-03-15 09:00:00'),
        'end' => Carbon::parse('2025-03-15 17:00:00'),
    ]);
    $service = app(ClosingAdminService::class);

    $data = $service->getEditFormData($closing);

    expect($data['closing']['start_date'])->toBe('15.03.2025')
        ->and($data['closing']['start_time'])->toBe('09:00')
        ->and($data['closing']['end_date'])->toBe('15.03.2025')
        ->and($data['closing']['end_time'])->toBe('17:00');
});

test('update modifies closing dates', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create([
        'start' => Carbon::parse('2025-03-10 09:00:00'),
        'end' => Carbon::parse('2025-03-10 17:00:00'),
    ]);
    $service = app(ClosingAdminService::class);

    $updated = $service->update($closing, [
        'start_date' => '20.03.2025',
        'start_time' => '10:00',
        'end_date' => '20.03.2025',
        'end_time' => '18:00',
        'description' => ['en' => 'Updated closing'],
    ]);

    expect($updated)->toBeInstanceOf(Closing::class)
        ->and($updated->id)->toBe($closing->id);
});

test('redirectData returns closable_id and closable_type', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();
    $service = app(ClosingAdminService::class);

    $data = $service->redirectData($closing);

    expect($data)->toHaveKey('closable_id')
        ->and($data)->toHaveKey('closable_type')
        ->and($data['closable_id'])->toBe($closing->closable_id)
        ->and($data['closable_type'])->toBe('institution');
});

test('redirectData works with resource closable', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $closing = Closing::factory()->for($resource, 'closable')->create();
    $service = app(ClosingAdminService::class);

    $data = $service->redirectData($closing);

    expect($data)->toHaveKey('closable_type')
        ->and($data['closable_type'])->toBe('resource');
});

test('getEditFormData closing array contains start_date field with formatted value', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create([
        'start' => Carbon::parse('2025-07-04 09:00:00'),
        'end' => Carbon::parse('2025-07-04 17:00:00'),
    ]);
    $service = app(ClosingAdminService::class);

    $data = $service->getEditFormData($closing);
    $closingArray = $data['closing'];

    // RemoveArrayItem mutation would drop start_date from the closing array
    expect($closingArray)->toHaveKey('start_date')
        ->and($closingArray['start_date'])->toBe('04.07.2025')
        ->and($closingArray)->toHaveKey('start_time')
        ->and($closingArray)->toHaveKey('end_date')
        ->and($closingArray)->toHaveKey('end_time');
});

test('update logs the updated closing', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create([
        'start' => Carbon::parse('2025-03-10 09:00:00'),
        'end' => Carbon::parse('2025-03-10 17:00:00'),
    ]);

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('updated', Mockery::type(Closing::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(ClosingAdminService::class);
    $service->update($closing, [
        'start_date' => '20.03.2025',
        'start_time' => '10:00',
        'end_date' => '20.03.2025',
        'end_time' => '18:00',
    ]);
});

test('delete logs the deleted closing', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('deleted', Mockery::type(Closing::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(ClosingAdminService::class);
    $service->delete($closing);
});

test('resolveClosingClosable aborts with 500 when closable is neither Institution nor Resource', function (): void {
    // InstanceOfToTrue mutation would skip the abort_unless, allowing invalid models through.
    // We test with a closing that has its closable relation null-loaded (detached morph).
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    // Overwrite the relation to return null
    $closing->setRelation('closable', null);

    $service = app(ClosingAdminService::class);

    // Should abort with 500
    expect(fn () => $service->redirectData($closing))->toThrow(HttpException::class);
});
