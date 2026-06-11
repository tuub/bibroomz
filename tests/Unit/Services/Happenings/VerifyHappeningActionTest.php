<?php

declare(strict_types=1);

use App\Exceptions\HappeningValidationException;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\VerifyHappeningAction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

covers(VerifyHappeningAction::class);

uses(RefreshDatabase::class);

test('execute throws when user is not allowed in resource group', function (): void {
    Event::fake();
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user1 = User::factory()->create(['is_admin' => false]);
    $user2 = User::factory()->create(['is_admin' => false]);
    $start = CarbonImmutable::now()->addHour();
    $end = CarbonImmutable::now()->addHours(2);
    $happening = Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $user1->id,
        'is_verified' => false,
        'start' => $start->format('Y-m-d H:i:s'),
        'end' => $end->format('Y-m-d H:i:s'),
    ]);

    $action = app(VerifyHappeningAction::class);

    expect(fn () => $action->execute($user2, $happening, $start, $end))
        ->toThrow(HappeningValidationException::class);
});
