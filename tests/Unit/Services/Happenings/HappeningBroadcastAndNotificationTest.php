<?php

covers(
    App\Services\Happenings\HappeningBroadcaster::class,
    App\Services\Happenings\HappeningBroadcastEventFactory::class,
    App\Services\Happenings\HappeningBroadcastPayloadFactory::class,
    App\Services\Happenings\HappeningNotificationService::class,
    App\Services\Happenings\HappeningNotificationTypeResolver::class
);

use App\Events\HappeningCreatedEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\HappeningUpdatedEvent;
use App\Events\HappeningVerifiedEvent;
use App\Library\Utility;
use App\Mail\HappeningMail;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\HappeningAudienceResolver;
use App\Services\Happenings\HappeningBroadcastPayloadFactory;
use App\Services\Happenings\HappeningNotificationService;
use App\Services\Happenings\HappeningNotificationTypeResolver;
use App\Services\Notifications\MailContentLookup;
use App\Services\Notifications\NotificationDispatchService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\InteractsWithPermissions;

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seedPermissions();
    Carbon::setTestNow(Carbon::parse('2026-06-03 09:00:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-03 09:00:00'));
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function createBroadcastFixture(array $attributes = []): array
{
    $institution = Institution::factory()->create(['email' => 'broadcast@example.test']);
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create([
        'resource_group_id' => $resourceGroup->id,
        'is_active' => true,
        'is_verification_required' => true,
    ]);
    $owner = User::factory()->create(['name' => 'owner.user']);
    $recipient = User::factory()->create(['name' => 'recipient.user']);
    $verifier = User::factory()->create(['name' => 'verifier.user']);

    $happening = Happening::create(array_merge([
        'user_id_01' => $owner->id,
        'user_id_02' => $recipient->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => Utility::normalizeLoginName($verifier->name),
        'start' => CarbonImmutable::parse('2026-06-03 10:00:00'),
        'end' => CarbonImmutable::parse('2026-06-03 11:00:00'),
        'reserved_at' => CarbonImmutable::parse('2026-06-03 09:00:00'),
        'verified_at' => null,
        'label' => Utility::getTranslatable('Study'),
    ], $attributes));

    return compact('institution', 'resourceGroup', 'resource', 'owner', 'recipient', 'verifier', 'happening');
}

test('audience resolver and broadcast payload factory build the same public happening view data', function () {
    $fixture = createBroadcastFixture();
    $this->grantPermission($fixture['owner'], $fixture['institution'], 'no_verifier');

    $audience = (new HappeningAudienceResolver())->resolve($fixture['happening']->fresh(['user1', 'user2']));
    $payload = (new HappeningBroadcastPayloadFactory())->make(
        $fixture['happening']->fresh(['resource.resource_group.institution', 'user1', 'user2']),
        $fixture['recipient'],
    );

    expect($audience->pluck('name')->all())->toContain(
        $fixture['owner']->name,
        $fixture['recipient']->name,
        $fixture['verifier']->name,
    );

    expect($payload['happening']['user_01'])->toBe($fixture['owner']->name)
        ->and($payload['happening']['resource']['id'])->toBe($fixture['resource']->id)
        ->and($payload['happening']['resource']['resourceGroupId'])->toBe($fixture['resourceGroup']->id)
        ->and($payload['happening']['isVerificationRequired'])->toBeFalse()
        ->and($payload['happening']['can'])->toBe([
            'verify' => false,
            'edit' => true,
            'delete' => true,
        ]);
});

test('notification type resolver maps each happening event to the expected mail key', function () {
    $fixture = createBroadcastFixture();
    $resolver = new HappeningNotificationTypeResolver();

    expect($resolver->resolve(new HappeningCreatedEvent($fixture['happening'], $fixture['recipient'])))
        ->toBe('happening_created_with_verification')
        ->and($resolver->resolve(new HappeningVerifiedEvent($fixture['happening'], $fixture['recipient'])))
        ->toBe('happening_verified')
        ->and($resolver->resolve(new HappeningUpdatedEvent($fixture['happening'], $fixture['recipient'])))
        ->toBe('happening_updated')
        ->and($resolver->resolve(new HappeningDeletedEvent($fixture['happening'], $fixture['recipient'])))
        ->toBe('happening_deleted');
});

test('notification service queues happening mail with pre resolved headers and skips inactive content', function () {
    $fixture = createBroadcastFixture();
    Mail::fake();

    $mailType = MailType::create([
        'key' => 'happening_created_with_verification',
        'description' => 'Booking created with verifier',
    ]);

    $activeContent = MailContent::create([
        'institution_id' => $fixture['institution']->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('Reservation created'),
        'title' => Utility::getTranslatable('Title'),
        'salutation' => Utility::getTranslatable('Hello'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'is_active' => true,
    ]);

    $service = new HappeningNotificationService(
        new HappeningNotificationTypeResolver(),
        new NotificationDispatchService(new MailContentLookup()),
    );
    $service->sendForEvent(new HappeningCreatedEvent(
        $fixture['happening']->fresh(['resource.resource_group.institution', 'user1', 'user2']),
        $fixture['recipient'],
    ));

    Mail::assertQueued(HappeningMail::class, function (HappeningMail $mail) use ($fixture, $activeContent) {
        $envelope = $mail->envelope();

        return $mail->data->envelope->fromAddress === $fixture['institution']->email
            && $mail->content->id === $activeContent->id
            && $envelope->from->address === $fixture['institution']->email
            && $envelope->replyTo[0]->address === $fixture['institution']->email;
    });

    $activeContent->update(['is_active' => false]);
    $service->sendForEvent(new HappeningCreatedEvent(
        $fixture['happening']->fresh(['resource.resource_group.institution', 'user1', 'user2']),
        $fixture['recipient'],
    ));

    Mail::assertQueued(HappeningMail::class, 1);
});
