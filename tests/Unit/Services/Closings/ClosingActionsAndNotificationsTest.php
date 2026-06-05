<?php

covers(
    App\Services\Closings\CreateClosingAction::class,
    App\Services\Closings\UpdateClosingAction::class,
    App\Services\Closings\DeleteClosingAction::class,
    App\Services\Closings\ListClosingsAction::class,
    App\Services\Closings\ClosingEventDispatcher::class,
    App\Services\Closings\ClosingNotificationService::class,
    App\Services\Closings\ClosingNotificationTypeResolver::class,
    App\Services\Closings\ClosingInstitutionResolver::class,
    App\Services\Closings\ClosingDataSanitizer::class
);

use App\Events\ClosingCreatedEvent;
use App\Events\ClosingDeletedEvent;
use App\Events\ClosingUpdatedEvent;
use App\Library\Utility;
use App\Mail\ClosingMail;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Closings\ClosingDataSanitizer;
use App\Services\Closings\ClosingEventDispatcher;
use App\Services\Closings\ClosingInstitutionResolver;
use App\Services\Closings\ClosingNotificationService;
use App\Services\Closings\ClosingNotificationTypeResolver;
use App\Services\Closings\CreateClosingAction;
use App\Services\Closings\DeleteClosingAction;
use App\Services\Closings\ListClosingsAction;
use App\Services\Closings\UpdateClosingAction;
use App\Services\Notifications\MailContentLookup;
use App\Services\Notifications\NotificationDispatchService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-03 09:00:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-03 09:00:00'));
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function createClosingFixture(): array
{
    $suffix = uniqid('.', true);
    $institution = Institution::factory()->create(['email' => 'closing' . $suffix . '@example.test']);
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create([
        'resource_group_id' => $resourceGroup->id,
        'is_active' => true,
    ]);
    $user = User::factory()->create(['name' => 'user' . $suffix]);

    $happening = Happening::create([
        'user_id_01' => $user->id,
        'user_id_02' => null,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => CarbonImmutable::parse('2026-06-03 10:00:00'),
        'end' => CarbonImmutable::parse('2026-06-03 11:00:00'),
        'reserved_at' => CarbonImmutable::parse('2026-06-03 09:00:00'),
        'verified_at' => CarbonImmutable::parse('2026-06-03 09:05:00'),
        'label' => Utility::getTranslatable('Study'),
    ]);

    return compact('institution', 'resourceGroup', 'resource', 'user', 'happening');
}

test('closing actions sanitize data, dispatch events, and preserve ordering', function () {
    Event::fake([
        ClosingCreatedEvent::class,
        ClosingUpdatedEvent::class,
        ClosingDeletedEvent::class,
    ]);

    $fixture = createClosingFixture();
    $dataSanitizer = new ClosingDataSanitizer();
    $dispatcher = new ClosingEventDispatcher();

    $createAction = new CreateClosingAction($dataSanitizer, $dispatcher);
    $updateAction = new UpdateClosingAction($dataSanitizer, $dispatcher);
    $deleteAction = new DeleteClosingAction($dispatcher);
    $listAction = new ListClosingsAction();

    $created = $createAction->execute($fixture['resource'], [
        'closable_id' => $fixture['resource']->id,
        'closable_type' => 'resource',
        'start_date' => '03.06.2026',
        'start_time' => '09:30',
        'end_date' => '03.06.2026',
        'end_time' => '10:30',
        'description' => Utility::getTranslatable('Exam period'),
    ]);

    expect($created->start->format('Y-m-d H:i:s'))->toBe('2026-06-03 09:30:00')
        ->and($created->getTranslation('description', 'en'))->toBe('Exam period');

    Event::assertDispatched(ClosingCreatedEvent::class, function (ClosingCreatedEvent $event) use ($fixture, $created) {
        return $event->user->is($fixture['user'])
            && $event->closing->is($created)
            && $event->happenings->pluck('id')->all() === [$fixture['happening']->id];
    });

    $updated = $updateAction->execute($created, [
        'start_date' => '03.06.2026',
        'start_time' => '09:00',
        'end_date' => '03.06.2026',
        'end_time' => '10:45',
        'description' => Utility::getTranslatable('Updated exam period'),
    ]);

    expect($updated->end->format('Y-m-d H:i:s'))->toBe('2026-06-03 10:45:00')
        ->and($updated->getTranslation('description', 'en'))->toBe('Updated exam period');

    Event::assertDispatched(ClosingUpdatedEvent::class, function (ClosingUpdatedEvent $event) use ($fixture, $updated) {
        return $event->user->is($fixture['user'])
            && $event->closing->is($updated)
            && $event->happenings->pluck('id')->all() === [$fixture['happening']->id];
    });

    $later = $createAction->execute($fixture['resource'], [
        'closable_id' => $fixture['resource']->id,
        'closable_type' => 'resource',
        'start_date' => '04.06.2026',
        'start_time' => '09:00',
        'end_date' => '04.06.2026',
        'end_time' => '10:00',
        'description' => Utility::getTranslatable('Later closing'),
    ]);

    expect($listAction->execute($fixture['resource'])->pluck('id')->all())->toBe([$updated->id, $later->id]);

    expect($deleteAction->execute($updated->fresh()))->toBeTrue();
    expect(Closing::withTrashed()->findOrFail($updated->id)->trashed())->toBeTrue();

    Event::assertDispatched(ClosingDeletedEvent::class, function (ClosingDeletedEvent $event) use ($fixture, $updated) {
        return $event->user->is($fixture['user'])
            && $event->closing->id === $updated->id
            && $event->happenings->pluck('id')->all() === [$fixture['happening']->id];
    });
});

test('closing notification services resolve institution and queue mail for active content only', function () {
    Mail::fake();
    $fixture = createClosingFixture();

    $closing = $fixture['resource']->closings()->create([
        'start' => CarbonImmutable::parse('2026-06-03 09:30:00'),
        'end' => CarbonImmutable::parse('2026-06-03 10:30:00'),
        'description' => Utility::getTranslatable('Exam period'),
    ]);

    $mailType = MailType::create([
        'key' => 'closing_created',
        'description' => 'Closing created',
    ]);

    $mailContent = MailContent::create([
        'institution_id' => $fixture['institution']->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('Closing created'),
        'title' => Utility::getTranslatable('Title'),
        'salutation' => Utility::getTranslatable('Hello'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'is_active' => true,
    ]);

    $institutionResolver = new ClosingInstitutionResolver();

    expect($institutionResolver->resolveForClosable($fixture['institution'])->is($fixture['institution']))->toBeTrue()
        ->and($institutionResolver->resolveForClosing($closing)->is($fixture['institution']))->toBeTrue()
        ->and((new ClosingNotificationTypeResolver())->resolve(
            new ClosingCreatedEvent($fixture['user'], collect([$fixture['happening']]), $closing),
        ))->toBe('closing_created')
        ->and((new ClosingNotificationTypeResolver())->resolve(
            new ClosingUpdatedEvent($fixture['user'], collect([$fixture['happening']]), $closing),
        ))->toBe('closing_updated')
        ->and((new ClosingNotificationTypeResolver())->resolve(
            new ClosingDeletedEvent($fixture['user'], collect([$fixture['happening']]), $closing),
        ))->toBe('closing_deleted');

    $service = new ClosingNotificationService(
        new ClosingNotificationTypeResolver(),
        $institutionResolver,
        new NotificationDispatchService(new MailContentLookup()),
    );

    $service->sendForEvent(new ClosingCreatedEvent(
        $fixture['user'],
        collect([$fixture['happening']->fresh(['resource.resource_group.institution', 'user1'])]),
        $closing->fresh('closable.resource_group.institution'),
    ));

    Mail::assertQueued(ClosingMail::class, function (ClosingMail $mail) use ($fixture, $mailContent, $closing) {
        $envelope = $mail->envelope();
        $content = $mail->content();

        return $mail->closing->is($closing)
            && $mail->data->envelope->fromAddress === $fixture['institution']->email
            && $mail->content->id === $mailContent->id
            && $envelope->from->address === $fixture['institution']->email
            && $envelope->replyTo[0]->address === $fixture['institution']->email
            && $content->text === 'emails.text.mail'
            && $content->markdown === 'emails.markdown.mail';
    });

    $mailContent->update(['is_active' => false]);
    $service->sendForEvent(new ClosingCreatedEvent(
        $fixture['user'],
        collect([$fixture['happening']]),
        $closing->fresh('closable.resource_group.institution'),
    ));

    Mail::assertQueued(ClosingMail::class, 1);
});

test('delete closing action returns false when the model cannot be deleted', function () {
    $dispatcher = Mockery::mock(ClosingEventDispatcher::class);
    $closing = Mockery::mock(Closing::class);

    $dispatcher->shouldReceive('dispatchDeleted')->never();
    $closing->shouldReceive('delete')->once()->andReturnFalse();

    expect((new DeleteClosingAction($dispatcher))->execute($closing))->toBeFalse();
});
