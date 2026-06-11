<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Console\AnonymizeHappeningUsersAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(AnonymizeHappeningUsersAction::class);

uses(RefreshDatabase::class);

test('query returns builder for happenings', function (): void {
    $action = new AnonymizeHappeningUsersAction;
    $query = $action->query(30);

    expect($query)->toBeInstanceOf(Builder::class);
});

test('execute anonymizes user references in happenings', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $user->id,
        'start' => now()->subDays(60),
        'end' => now()->subDays(60)->addHours(2),
    ]);

    $action = new AnonymizeHappeningUsersAction;
    $query = $action->query(30);
    $action->execute($query);

    expect(Happening::find($happening->id)?->user_id_01)->toBeNull();
});
