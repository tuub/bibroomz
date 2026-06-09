<?php

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
use App\Services\Happenings\HappeningBroadcaster;
use App\Services\Happenings\HappeningBroadcastEventFactory;
use App\Services\Happenings\HappeningBroadcastPayloadFactory;
use App\Services\Happenings\HappeningNotificationService;
use App\Services\Happenings\HappeningNotificationTypeResolver;
use App\Services\Notifications\MailContentLookup;
use App\Services\Notifications\NotificationDispatchService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\InteractsWithPermissions;

covers(
    HappeningBroadcaster::class,
    HappeningBroadcastEventFactory::class,
    HappeningBroadcastPayloadFactory::class,
    HappeningNotificationService::class,
    HappeningNotificationTypeResolver::class
);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seedPermissions();
    Carbon::setTestNow(Carbon::parse('2026-06-03 09:00:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-03 09:00:00'));
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

/**
 * @param  array<string, mixed>  $attributes
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource, owner: User, recipient: User, verifier: User, happening: Happening}
 */
/**
 * @param  array<string, mixed>  $attributes
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: App\Models\Resource, owner: User, recipient: User, verifier: User, happening: Happening}
 */
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

    /** @var array<string, mixed> $mergedAttrs */
    $mergedAttrs = array_merge([
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
    ], $attributes);
    /** @var Happening $happening */
    $happening = Happening::create($mergedAttrs);

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource, 'owner' => $owner, 'recipient' => $recipient, 'verifier' => $verifier, 'happening' => $happening];
}

test('audience resolver and broadcast payload factory build the same public happening view data', function (): void {
    $fixture = createBroadcastFixture();
    $this->grantPermission($fixture['owner'], $fixture['institution'], 'no_verifier');

    /** @var Happening $happeningForAudience */
    $happeningForAudience = $fixture['happening']->fresh(['user1', 'user2']);
    $audience = (new HappeningAudienceResolver)->resolve($happeningForAudience);
    /** @var Happening $happeningForPayload */
    $happeningForPayload = $fixture['happening']->fresh(['resource.resource_group.institution', 'user1', 'user2']);
    $payload = (new HappeningBroadcastPayloadFactory)->make(
        $happeningForPayload,
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

test('notification type resolver maps each happening event to the expected mail key', function (): void {
    $fixture = createBroadcastFixture();
    $resolver = new HappeningNotificationTypeResolver;

    expect($resolver->resolve(new HappeningCreatedEvent($fixture['happening'], $fixture['recipient'])))
        ->toBe('happening_created_with_verification')
        ->and($resolver->resolve(new HappeningVerifiedEvent($fixture['happening'], $fixture['recipient'])))
        ->toBe('happening_verified')
        ->and($resolver->resolve(new HappeningUpdatedEvent($fixture['happening'], $fixture['recipient'])))
        ->toBe('happening_updated')
        ->and($resolver->resolve(new HappeningDeletedEvent($fixture['happening'], $fixture['recipient'])))
        ->toBe('happening_deleted');
});

test('notification service queues happening mail with pre resolved headers and skips inactive content', function (): void {
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
        new HappeningNotificationTypeResolver,
        new NotificationDispatchService(new MailContentLookup),
    );
    $service->sendForEvent(new HappeningCreatedEvent(
        $fixture['happening']->fresh(['resource.resource_group.institution', 'user1', 'user2']) ?? $fixture['happening'],
        $fixture['recipient'],
    ));

    Mail::assertQueued(HappeningMail::class, function (HappeningMail $mail) use ($fixture, $activeContent): bool {
        $envelope = $mail->envelope();
        $fromAddress = $envelope->from instanceof Address ? $envelope->from->address : null;
        $replyToAddress = isset($envelope->replyTo[0]) && $envelope->replyTo[0] instanceof Address ? $envelope->replyTo[0]->address : null;

        return $mail->data->envelope->fromAddress === $fixture['institution']->email
            && $mail->content->id === $activeContent->id
            && $fromAddress === $fixture['institution']->email
            && $replyToAddress === $fixture['institution']->email;
    });

    $activeContent->update(['is_active' => false]);
    $service->sendForEvent(new HappeningCreatedEvent(
        $fixture['happening']->fresh(['resource.resource_group.institution', 'user1', 'user2']) ?? $fixture['happening'],
        $fixture['recipient'],
    ));

    Mail::assertQueued(HappeningMail::class, 1);
});
