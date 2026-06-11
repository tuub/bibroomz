<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\MailController;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\User;
use Database\Seeders\MailTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

covers(MailController::class);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(MailTypeSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Create an actor who can access the admin panel but has no permissions in the target institution. */
function buildScopedActorForMails(Institution $institution): User
{
    $actor = User::factory()->create();
    grantAdminPermission($actor, $institution, 'view_users');

    return $actor;
}

// ---------------------------------------------------------------------------
// From AdminPermissionMatrixTest — mail tests
// ---------------------------------------------------------------------------

test('scoped admin without create_mails cannot store mail', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->firstOrFail();
    $actor = buildScopedActorForMails($institution);

    $this->actingAs($actor)
        ->post(route('admin.mail.store'), [
            'institution_id' => $institution->id,
            'mail_type_id' => $mailType->id,
            'subject' => Utility::getTranslatable('Unauthorized mail'),
            'title' => Utility::getTranslatable('Title'),
            'salutation' => Utility::getTranslatable('Hello'),
            'intro' => Utility::getTranslatable('Intro'),
            'outro' => Utility::getTranslatable('Outro'),
            'farewell' => Utility::getTranslatable('Bye'),
            'is_active' => true,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('mail_contents', ['institution_id' => $institution->id]);
});

test('scoped admin without edit_mails cannot update mail', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->firstOrFail();
    $mail = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('Original subject'),
        'title' => Utility::getTranslatable('Original title'),
        'salutation' => Utility::getTranslatable('Hello'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'is_active' => true,
    ]);
    $actor = buildScopedActorForMails($institution);

    $this->actingAs($actor)
        ->post(route('admin.mail.update'), [
            'id' => $mail->id,
            'institution_id' => $institution->id,
            'mail_type_id' => $mailType->id,
            'subject' => Utility::getTranslatable('Unauthorized subject'),
            'title' => Utility::getTranslatable('Unauthorized title'),
            'salutation' => Utility::getTranslatable('Hi'),
            'intro' => Utility::getTranslatable('Intro'),
            'outro' => Utility::getTranslatable('Outro'),
            'farewell' => Utility::getTranslatable('Bye'),
            'is_active' => true,
        ])
        ->assertForbidden();

    expect($mail->fresh()?->getTranslations('subject')['en'])->toBe('Original subject');
});

test('scoped admin without view_mails cannot view mails index', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForMails($institution);

    $this->actingAs($actor)
        ->get(route('admin.mail.index', ['institution_id' => $institution->id]))
        ->assertForbidden();
});

test('scoped admin without create_mails cannot view mail create form', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForMails($institution);

    $this->actingAs($actor)
        ->get(route('admin.mail.create', ['institution_id' => $institution->id]))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Success paths
// ---------------------------------------------------------------------------

test('storeMail returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->firstOrFail();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_mails');
    $this->actingAs($actor);

    $this->post(route('admin.mail.store'), [
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('New subject'),
        'title' => Utility::getTranslatable('New title'),
        'salutation' => Utility::getTranslatable('Hello'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'is_active' => true,
    ])->assertRedirect(route('admin.mail.index', ['institution_id' => $institution->id]));

    $this->assertDatabaseHas('mail_contents', ['institution_id' => $institution->id]);
});

test('updateMail returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->firstOrFail();
    $mail = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('Old subject'),
        'title' => Utility::getTranslatable('Old title'),
        'salutation' => Utility::getTranslatable('Hello'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'is_active' => true,
    ]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_mails');
    $this->actingAs($actor);

    $this->post(route('admin.mail.update'), [
        'id' => $mail->id,
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('Updated subject'),
        'title' => Utility::getTranslatable('Updated title'),
        'salutation' => Utility::getTranslatable('Hi'),
        'intro' => Utility::getTranslatable('Updated intro'),
        'outro' => Utility::getTranslatable('Updated outro'),
        'farewell' => Utility::getTranslatable('Updated bye'),
        'is_active' => true,
    ])->assertRedirect(route('admin.mail.index', ['institution_id' => $institution->id]));

    expect($mail->fresh()?->getTranslation('subject', 'en'))->toBe('Updated subject');
});

test('deleteMail returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->firstOrFail();
    $mail = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('To delete'),
        'title' => Utility::getTranslatable('Title'),
        'salutation' => Utility::getTranslatable('Hello'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'is_active' => true,
    ]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'delete_mails');
    $this->actingAs($actor);

    $this->post(route('admin.mail.delete'), ['id' => $mail->id])
        ->assertRedirect(route('admin.mail.index', ['institution_id' => $institution->id]));

    $this->assertDatabaseMissing('mail_contents', ['id' => $mail->id]);
});

// ---------------------------------------------------------------------------
// Redirect for non-existent ID
// ---------------------------------------------------------------------------

test('editMail returns redirect for non-existent id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForMails($institution);

    $this->actingAs($actor)
        ->get(route('admin.mail.edit', ['id' => (string) Str::uuid()]))
        ->assertRedirect();
});

test('mailIndex returns redirect for non-existent institution id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForMails($institution);

    $this->actingAs($actor)
        ->get(route('admin.mail.index', ['institution_id' => (string) Str::uuid()]))
        ->assertRedirect();
});

test('mailCreate returns redirect for non-existent institution id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForMails($institution);

    $this->actingAs($actor)
        ->get(route('admin.mail.create', ['institution_id' => (string) Str::uuid()]))
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// Redirect on validation failure (form POST)
// ---------------------------------------------------------------------------

test('storeMail returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_mails');

    // Provide institution_id to pass authorize(), but omit required fields like subject/mail_type_id
    $this->actingAs($actor)
        ->post(route('admin.mail.store'), [
            'institution_id' => $institution->id,
            'is_active' => true,
        ])
        ->assertRedirect();
});

test('updateMail returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->firstOrFail();
    $mail = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('Subject'),
        'title' => Utility::getTranslatable('Title'),
        'salutation' => Utility::getTranslatable('Hello'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'is_active' => true,
    ]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_mails');

    $this->actingAs($actor)
        ->post(route('admin.mail.update'), ['id' => $mail->id])
        ->assertRedirect();
});

test('deleteMail returns 403 for user without delete_mails permission', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->firstOrFail();
    $mail = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('Protected subject'),
        'title' => Utility::getTranslatable('Protected title'),
        'salutation' => Utility::getTranslatable('Hello'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'is_active' => true,
    ]);
    $actor = buildScopedActorForMails($institution);

    $this->actingAs($actor)
        ->post(route('admin.mail.delete'), ['id' => $mail->id])
        ->assertForbidden();

    $this->assertDatabaseHas('mail_contents', ['id' => $mail->id]);
});

test('scoped admin without edit_mails cannot view mail edit form', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->firstOrFail();
    $mail = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('Existing subject'),
        'title' => Utility::getTranslatable('Existing title'),
        'salutation' => Utility::getTranslatable('Hello'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'is_active' => true,
    ]);
    $actor = buildScopedActorForMails($institution);

    $this->actingAs($actor)
        ->get(route('admin.mail.edit', ['id' => $mail->id]))
        ->assertForbidden();
});
