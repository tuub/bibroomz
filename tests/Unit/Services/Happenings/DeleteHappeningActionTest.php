<?php

declare(strict_types=1);

use App\Events\HappeningDeletedEvent;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\DeleteHappeningAction;
use App\Services\Happenings\HappeningBroadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(DeleteHappeningAction::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

test('execute deletes the happening', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);
    $id = $happening->id;

    $action = app(DeleteHappeningAction::class);
    $action->execute($happening);

    expect(Happening::find($id))->toBeNull();
});

test('execute returns true when happening is successfully deleted', function (): void {
    // TrueToFalse would change 'return true' to 'return false'
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $action = app(DeleteHappeningAction::class);
    $result = $action->execute($happening);

    expect($result)->toBeTrue();
});

test('execute broadcasts HappeningDeletedEvent after deletion', function (): void {
    // RemoveMethodCall would remove the broadcaster->broadcast() call
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $action = app(DeleteHappeningAction::class);
    $action->execute($happening);

    Event::assertDispatched(HappeningDeletedEvent::class);
});

test('execute returns false when happening cannot be deleted', function (): void {
    // IfNegated would change 'if (!delete())' to 'if (delete())', making it return false on success
    // RemoveNot would have the same effect
    // We test with a mock that returns false from delete()
    $happening = Mockery::mock(Happening::class);
    $happening->shouldReceive('delete')->once()->andReturn(false);

    $broadcaster = Mockery::mock(HappeningBroadcaster::class);
    $broadcaster->shouldNotReceive('broadcast');

    $action = new DeleteHappeningAction($broadcaster);
    $result = $action->execute($happening);

    expect($result)->toBeFalse();
});
