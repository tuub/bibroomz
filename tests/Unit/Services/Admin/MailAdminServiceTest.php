<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\User;
use App\Services\Admin\MailAdminService;
use App\Services\AdminLoggingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(MailAdminService::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

// -------------------------------------------------------------------------
// getIndexData
// -------------------------------------------------------------------------

test('getIndexData returns mails key', function (): void {
    $institution = Institution::factory()->create();

    $service = app(MailAdminService::class);
    $data = $service->getIndexData($institution);

    expect($data)->toHaveKey('mails');
});

test('getIndexData returns institution key', function (): void {
    $institution = Institution::factory()->create();

    $service = app(MailAdminService::class);
    $data = $service->getIndexData($institution);

    expect($data)->toHaveKey('institution');
});

test('getIndexData institution is the passed model', function (): void {
    $institution = Institution::factory()->create();

    $service = app(MailAdminService::class);
    $data = $service->getIndexData($institution);

    expect($data['institution']->id)->toBe($institution->id);
});

test('getIndexData returns mails for the given institution only', function (): void {
    $institution = Institution::factory()->create();
    $other = Institution::factory()->create();
    $mailType1 = MailType::factory()->create(['key' => 'type_a_'.uniqid()]);
    $mailType2 = MailType::factory()->create(['key' => 'type_b_'.uniqid()]);
    MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType1->id]);
    MailContent::factory()->for($other, 'institution')->create(['mail_type_id' => $mailType2->id]);

    $service = app(MailAdminService::class);
    $data = $service->getIndexData($institution);

    expect($data['mails'])->toHaveCount(1);
});

test('getIndexData filters mails by user visibility when logged in', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'type_c_'.uniqid()]);
    MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $service = app(MailAdminService::class);
    $data = $service->getIndexData($institution);

    expect($data['mails'])->toHaveCount(1);
});

test('getIndexData returns all mails when no user is logged in', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'type_d_'.uniqid()]);
    MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    $service = app(MailAdminService::class);
    $data = $service->getIndexData($institution);

    expect($data['mails'])->toHaveCount(1);
});

// -------------------------------------------------------------------------
// getCreateFormData
// -------------------------------------------------------------------------

test('getCreateFormData returns institution_id key', function (): void {
    $institution = Institution::factory()->create();

    $service = app(MailAdminService::class);
    $data = $service->getCreateFormData($institution);

    expect($data)->toHaveKey('institution_id');
});

test('getCreateFormData returns mail_types key', function (): void {
    $institution = Institution::factory()->create();

    $service = app(MailAdminService::class);
    $data = $service->getCreateFormData($institution);

    expect($data)->toHaveKey('mail_types');
});

test('getCreateFormData returns languages key', function (): void {
    $institution = Institution::factory()->create();

    $service = app(MailAdminService::class);
    $data = $service->getCreateFormData($institution);

    expect($data)->toHaveKey('languages');
});

test('getCreateFormData institution_id matches institution', function (): void {
    $institution = Institution::factory()->create();

    $service = app(MailAdminService::class);
    $data = $service->getCreateFormData($institution);

    expect($data['institution_id'])->toBe($institution->id);
});

// -------------------------------------------------------------------------
// getEditFormData
// -------------------------------------------------------------------------

test('getEditFormData returns mail key', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'edit_type_'.uniqid()]);
    $mail = MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    $service = app(MailAdminService::class);
    $data = $service->getEditFormData($mail);

    expect($data)->toHaveKey('mail');
});

test('getEditFormData returns institution_id key', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'edit_type2_'.uniqid()]);
    $mail = MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    $service = app(MailAdminService::class);
    $data = $service->getEditFormData($mail);

    expect($data)->toHaveKey('institution_id');
});

test('getEditFormData returns mail_types key', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'edit_type3_'.uniqid()]);
    $mail = MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    $service = app(MailAdminService::class);
    $data = $service->getEditFormData($mail);

    expect($data)->toHaveKey('mail_types');
});

test('getEditFormData returns languages key', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'edit_type4_'.uniqid()]);
    $mail = MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    $service = app(MailAdminService::class);
    $data = $service->getEditFormData($mail);

    expect($data)->toHaveKey('languages');
});

test('getEditFormData institution_id matches mail institution', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'edit_type5_'.uniqid()]);
    $mail = MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    $service = app(MailAdminService::class);
    $data = $service->getEditFormData($mail);

    expect($data['institution_id'])->toBe($institution->id);
});

// -------------------------------------------------------------------------
// store
// -------------------------------------------------------------------------

test('store creates mail content', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'booking_created']);

    $service = app(MailAdminService::class);
    $mc = $service->store([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => ['en' => 'Hello', 'de' => 'Hallo'],
        'body' => ['en' => 'Body text', 'de' => 'Körpertext'],
        'is_active' => true,
    ]);

    expect($mc)->toBeInstanceOf(MailContent::class)
        ->and($mc->institution_id)->toBe($institution->id);
});

// -------------------------------------------------------------------------
// update
// -------------------------------------------------------------------------

test('update saves changed attributes and returns mail', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'update_type_'.uniqid()]);
    $mail = MailContent::factory()->for($institution, 'institution')->create([
        'mail_type_id' => $mailType->id,
        'is_active' => false,
    ]);

    $service = app(MailAdminService::class);
    $updated = $service->update($mail, ['is_active' => true]);

    expect($updated)->toBeInstanceOf(MailContent::class)
        ->and($updated->is_active)->toBeTrue()
        ->and(MailContent::findOrFail($mail->id)->is_active)->toBeTrue();
});

// -------------------------------------------------------------------------
// delete
// -------------------------------------------------------------------------

test('delete removes mail content', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'booking_deleted_'.uniqid()]);
    $mc = MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);
    $id = $mc->id;

    $service = app(MailAdminService::class);
    $service->delete($mc);

    expect(MailContent::find($id))->toBeNull();
});

// -------------------------------------------------------------------------
// eager load relations (RemoveArrayItem)
// -------------------------------------------------------------------------

test('getIndexData mails have mail_type relation loaded', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'rel_type_'.uniqid()]);
    MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    $service = app(MailAdminService::class);
    $data = $service->getIndexData($institution);

    $mail = $data['mails']->first();
    // RemoveArrayItem mutation would remove 'mail_type' from the with() call
    expect($mail->relationLoaded('mail_type'))->toBeTrue();
});

test('getIndexData mails have institution relation loaded', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'rel_inst_type_'.uniqid()]);
    MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    $service = app(MailAdminService::class);
    $data = $service->getIndexData($institution);

    $mail = $data['mails']->first();
    // RemoveArrayItem mutation would remove 'institution' from the with() call
    expect($mail->relationLoaded('institution'))->toBeTrue();
});

// -------------------------------------------------------------------------
// InstanceOfToFalse: when user is not a User instance, skip filter
// -------------------------------------------------------------------------

test('getIndexData does not filter mails when no user logged in (InstanceOfToFalse)', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'nofilter_'.uniqid()]);
    MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    // Not logged in = auth()->user() returns null, which is not instanceof User
    $service = app(MailAdminService::class);
    $data = $service->getIndexData($institution);

    // InstanceOfToFalse would make the condition always false, so it always skips filtering
    // In both cases (logged-in admin or null) we should get 1 mail here
    expect($data['mails'])->toHaveCount(1);
});

test('getIndexData filters out mails a restricted non-admin user cannot view', function (): void {
    // InstanceOfToFalse on line 28 changes "if ($user instanceof User)" to "if (false)",
    // skipping the filter entirely — a non-admin with no roles who can view nothing would
    // then incorrectly see all mails.
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'restricted_mail_'.uniqid()]);
    MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    $user = User::factory()->create(['is_admin' => false]); // no roles → cannot view any mails
    $this->actingAs($user);

    $service = app(MailAdminService::class);
    $data = $service->getIndexData($institution);

    // With original: filter runs, user has no view_mails permission → count = 0
    // With InstanceOfToFalse: filter skipped → count = 1
    expect($data['mails'])->toHaveCount(0);
});

// -------------------------------------------------------------------------
// logging side effects
// -------------------------------------------------------------------------

test('store logs the created mail content', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'log_create_'.uniqid()]);

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('created', Mockery::type(MailContent::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(MailAdminService::class);
    $service->store([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => ['en' => 'Subj'],
        'body' => ['en' => 'Body'],
        'is_active' => true,
    ]);
});

test('update logs the updated mail content', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'log_update_'.uniqid()]);
    $mc = MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('updated', Mockery::type(MailContent::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(MailAdminService::class);
    $service->update($mc, ['is_active' => true]);
});

test('delete logs the deleted mail content', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'log_delete_'.uniqid()]);
    $mc = MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    $loggingService = Mockery::mock(AdminLoggingService::class);
    $loggingService->shouldReceive('log')->once()->with('deleted', Mockery::type(MailContent::class));
    app()->instance(AdminLoggingService::class, $loggingService);

    $service = app(MailAdminService::class);
    $service->delete($mc);
});
