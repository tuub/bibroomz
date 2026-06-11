<?php

declare(strict_types=1);

use App\Http\Requests\Admin\DeleteMailContentRequest;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\User;
use Database\Seeders\MailTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(DeleteMailContentRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seedPermissions();
    $this->seed(MailTypeSeeder::class);
});

test('DeleteMailContentRequest defines validation rules', function (): void {
    $request = new DeleteMailContentRequest;

    expect($request->rules())->toBeArray();
});

test('DeleteMailContentRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new DeleteMailContentRequest;

    expect($request->authorize())->toBeFalse();
});

test('rules returns all required id validation rules', function (): void {
    $request = new DeleteMailContentRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:mail_contents,id');
});

test('DeleteMailContentRequest authorize returns true when user can delete mail content', function (): void {
    $institution = Institution::factory()->create();
    $mailContent = MailContent::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_mails');

    $request = buildAdminFormRequest(DeleteMailContentRequest::class, ['id' => $mailContent->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('DeleteMailContentRequest authorize returns false when actor is null even with mail content', function (): void {
    $institution = Institution::factory()->create();
    $mailContent = MailContent::factory()->for($institution, 'institution')->create();
    $request = buildFormRequest(DeleteMailContentRequest::class, ['id' => $mailContent->id]);

    expect($request->authorize())->toBeFalse();
});

test('DeleteMailContentRequest authorize returns false when mail content is null even with actor', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $request = buildAdminFormRequest(DeleteMailContentRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('DeleteMailContentRequest mailContent accessor returns correct model', function (): void {
    $institution = Institution::factory()->create();
    $mailContent = MailContent::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_mails');

    $request = buildAdminFormRequest(DeleteMailContentRequest::class, ['id' => $mailContent->id], $user);
    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->mailContent()->id)->toBe($mailContent->id);
});
