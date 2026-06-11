<?php

use App\Http\Requests\Admin\MailContentIdRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(MailContentIdRequest::class);

uses(RefreshDatabase::class);

test('authorize returns true', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(MailContentIdRequest::class, [], $user);
    expect($request->authorize())->toBeTrue();
});

test('mailContent accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::create(['key' => 'test_type', 'description' => 'Test type']);
    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('Subject'),
        'title' => Utility::getTranslatable('Title'),
        'salutation' => Utility::getTranslatable('Hi'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'is_active' => true,
    ]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(MailContentIdRequest::class, ['id' => $mailContent->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->mailContent()->id)->toBe($mailContent->id);
});

test('mailContent accessor throws when model not found', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::create(['key' => 'test_type_2', 'description' => 'Test type 2']);
    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('Subject'),
        'title' => Utility::getTranslatable('Title'),
        'salutation' => Utility::getTranslatable('Hi'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'is_active' => true,
    ]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(MailContentIdRequest::class, ['id' => $mailContent->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $mailContent->delete();

    expect(fn () => $request->mailContent())->toThrow(ModelNotFoundException::class);
});

test('rules returns all required id validation rules', function (): void {
    $request = new MailContentIdRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:mail_contents,id');
});
