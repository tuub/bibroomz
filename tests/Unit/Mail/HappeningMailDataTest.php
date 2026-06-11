<?php

declare(strict_types=1);

use App\Mail\HappeningMailData;
use App\Mail\MailEnvelopeData;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(HappeningMailData::class);

uses(RefreshDatabase::class);

test('stores all constructor arguments as public properties', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);
    $content = MailContent::factory()->create();
    $envelope = new MailEnvelopeData('from@example.com');

    $data = new HappeningMailData($happening, $content, $envelope);

    expect($data->happening)->toBe($happening)
        ->and($data->content)->toBe($content)
        ->and($data->envelope)->toBe($envelope);
});
