<?php

declare(strict_types=1);

use App\Http\Requests\Admin\MailContentRequest;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\User;
use Database\Seeders\MailTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(MailContentRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seedPermissions();
    $this->seed(MailTypeSeeder::class);
});

test('MailContentRequest defines validation rules', function (): void {
    $request = new MailContentRequest;

    expect($request->rules())->toBeArray();
});

test('MailContentRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new MailContentRequest;

    expect($request->authorize())->toBeFalse();
});

test('rules contains all expected keys', function (): void {
    $rules = (new MailContentRequest)->rules();

    expect($rules)
        ->toHaveKey('id')
        ->toHaveKey('institution_id')
        ->toHaveKey('mail_type_id')
        ->toHaveKey('subject')
        ->toHaveKey('title')
        ->toHaveKey('salutation')
        ->toHaveKey('intro')
        ->toHaveKey('outro')
        ->toHaveKey('action_uri')
        ->toHaveKey('action_uri_label')
        ->toHaveKey('farewell')
        ->toHaveKey('is_active');
});

test('id field rules contain nullable uuid exists mail_contents', function (): void {
    $rules = (new MailContentRequest)->rules();

    expect($rules['id'])
        ->toContain('nullable')
        ->toContain('uuid')
        ->toContain('exists:mail_contents,id');
});

test('institution_id field rules contain required uuid exists institutions', function (): void {
    $rules = (new MailContentRequest)->rules();

    expect($rules['institution_id'])
        ->toContain('required')
        ->toContain('uuid')
        ->toContain('exists:institutions,id');
});

test('mail_type_id field rules contain required and exists', function (): void {
    $rules = (new MailContentRequest)->rules();

    expect($rules['mail_type_id'])
        ->toContain('required')
        ->toContain('exists:mail_types,id');
});

test('subject field rules contain required', function (): void {
    $rules = (new MailContentRequest)->rules();

    expect($rules['subject'])->toContain('required');
});

test('action_uri_label field rules contain required_with:action_uri', function (): void {
    $rules = (new MailContentRequest)->rules();

    expect($rules['action_uri_label'])->toContain('required_with:action_uri');
});

test('is_active field rules contain required and boolean', function (): void {
    $rules = (new MailContentRequest)->rules();

    expect($rules['is_active'])
        ->toContain('required')
        ->toContain('boolean');
});

test('institution_id must be uuid', function (): void {
    $mailType = MailType::query()->first();
    $rules = (new MailContentRequest)->rules();

    $v = Validator::make([
        'institution_id' => 'not-a-uuid',
        'mail_type_id' => $mailType?->id,
        'subject' => 'Test',
        'is_active' => true,
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('institution_id'))->toBeTrue();
});

test('mail_type_id is required', function (): void {
    $institution = Institution::factory()->create();
    $rules = (new MailContentRequest)->rules();

    $v = Validator::make([
        'institution_id' => $institution->id,
        'subject' => 'Test',
        'is_active' => true,
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('mail_type_id'))->toBeTrue();
});

test('subject is required', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->first();
    $rules = (new MailContentRequest)->rules();

    $v = Validator::make([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType?->id,
        'is_active' => true,
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('subject'))->toBeTrue();
});

test('is_active rejects non-boolean', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->first();
    $rules = (new MailContentRequest)->rules();

    $v = Validator::make([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType?->id,
        'subject' => 'Test',
        'is_active' => 'yes',
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('is_active'))->toBeTrue();
});

test('action_uri_label required when action_uri present', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->first();
    $rules = (new MailContentRequest)->rules();

    $v = Validator::make([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType?->id,
        'subject' => 'Test',
        'is_active' => true,
        'action_uri' => 'https://example.com',
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('action_uri_label'))->toBeTrue();
});

test('institutionOrNull returns null when no institution_id given', function (): void {
    $request = buildFormRequest(MailContentRequest::class, []);

    expect($request->institutionOrNull())->toBeNull();
});

test('institutionOrNull returns institution model for valid id', function (): void {
    $institution = Institution::factory()->create();
    $request = buildFormRequest(MailContentRequest::class, ['institution_id' => $institution->id]);

    expect($request->institutionOrNull()?->id)->toBe($institution->id);
});

test('mailContentOrNull returns null when no id given', function (): void {
    $request = buildFormRequest(MailContentRequest::class, []);

    expect($request->mailContentOrNull())->toBeNull();
});

test('authorize returns false when no user', function (): void {
    $request = buildFormRequest(MailContentRequest::class, []);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user is not a User instance', function (): void {
    // InstanceOfToTrue would make (! $user instanceof User) always false, always proceeding.
    // Test: no user → should return false.
    $request = buildFormRequest(MailContentRequest::class, []);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when no user is resolved even with an institution id present', function (): void {
    $institution = Institution::factory()->create();
    $request = buildFormRequest(MailContentRequest::class, ['institution_id' => $institution->id]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when no user is resolved even with a mail id present', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->first();
    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType?->id,
        'subject' => ['en' => 'Test'],
        'body' => ['en' => 'Body'],
        'is_active' => true,
    ]);
    $request = buildFormRequest(MailContentRequest::class, ['id' => $mailContent->id]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when the resolved user is not an App user even with an institution id present', function (): void {
    $institution = Institution::factory()->create();
    $request = MailContentRequest::create('/', 'POST', ['institution_id' => $institution->id]);
    $request->setUserResolver(fn (): stdClass => new stdClass);
    $request->setContainer(app());

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when mail is null and institution is null', function (): void {
    // BooleanAndToBooleanOr: $institution instanceof Institution && $user->can(...) becomes ||
    // When institution is null: null instanceof Institution = false → && gives false (correct)
    // With ||: false || result-of-can() which may be true if user is admin.
    // Test: admin user but no institution → should return false (neither mail nor institution)
    $user = User::factory()->create(['is_admin' => true]);
    $request = buildFormRequest(MailContentRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true for create path when user can create mail content for institution', function (): void {
    // RemoveEarlyReturn would remove early return when no user, causing fall-through.
    // Also tests the BooleanAndToBooleanOr kill path: institution is set and user can create.
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'create_mails');

    $request = buildAdminFormRequest(MailContentRequest::class, ['institution_id' => $institution->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('authorize returns true for edit path when mail exists and user can edit it', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->first();
    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType?->id,
        'subject' => ['en' => 'Test'],
        'body' => ['en' => 'Body'],
        'is_active' => true,
    ]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_mails');

    $request = buildAdminFormRequest(MailContentRequest::class, [
        'id' => $mailContent->id,
        'institution_id' => $institution->id,
    ], $user);

    expect($request->authorize())->toBeTrue();
});
