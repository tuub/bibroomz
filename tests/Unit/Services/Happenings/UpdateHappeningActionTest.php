<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\UpdateHappeningAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

covers(UpdateHappeningAction::class);

uses(RefreshDatabase::class);

test('executeForAdmin updates happening attributes', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $user->id,
        'is_verified' => false,
    ]);

    $action = app(UpdateHappeningAction::class);
    $updated = $action->executeForAdmin($happening, ['is_verified' => true]);

    expect($updated->is_verified)->toBeTrue();
});
