<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Console\RemoveUnverifiedHappeningsAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

covers(RemoveUnverifiedHappeningsAction::class);

uses(RefreshDatabase::class);

test('execute deletes happenings matching query', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $user->id,
        'is_verified' => false,
        'start' => now()->subDays(5),
        'end' => now()->subDays(5)->addHours(2),
    ]);
    $id = $happening->id;

    $action = new RemoveUnverifiedHappeningsAction;
    $query = Happening::query()->where('id', $id);
    $action->execute($query);

    expect(Happening::find($id))->toBeNull();
});
