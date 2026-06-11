<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\CreateHappeningAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

covers(CreateHappeningAction::class);

uses(RefreshDatabase::class);

test('executeForAdmin creates a happening', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    $action = app(CreateHappeningAction::class);
    $happening = $action->executeForAdmin([
        'user_id_01' => $user->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'start' => now()->addHour()->format('Y-m-d H:i:s'),
        'end' => now()->addHours(2)->format('Y-m-d H:i:s'),
    ]);

    expect($happening)->toBeInstanceOf(Happening::class)
        ->and($happening->id)->not->toBeNull();
});
