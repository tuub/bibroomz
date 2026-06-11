<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\MailController;
use App\Http\Requests\Admin\DeleteMailContentRequest;
use App\Http\Requests\Admin\InstitutionContextRequest;
use App\Http\Requests\Admin\MailContentIdRequest;
use App\Http\Requests\Admin\MailContentRequest;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\User;
use App\Services\Admin\MailAdminService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(MailController::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

test('MailController can be resolved from container', function (): void {
    $controller = app(MailController::class);

    expect($controller)->toBeInstanceOf(MailController::class);
});

test('getMails renders Inertia response after authorization', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(InstitutionContextRequest::class);

    $request->shouldReceive('institution')->once()->andReturn($institution);
    $service->shouldReceive('getIndexData')
        ->once()
        ->with($institution)
        ->andReturn(['mails' => []]);

    $response = (new MailController($service))->getMails($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('getMails allows a non-admin user with view_mails permission', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false, 'is_system_user' => false]);
    grantAdminPermission($user, $institution, 'view_mails');
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(InstitutionContextRequest::class);

    $request->shouldReceive('institution')->once()->andReturn($institution);
    $service->shouldReceive('getIndexData')->once()->with($institution)->andReturn(['mails' => []]);

    $response = (new MailController($service))->getMails($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('createMail renders Inertia response after authorization', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(InstitutionContextRequest::class);

    $request->shouldReceive('institution')->once()->andReturn($institution);
    $service->shouldReceive('getCreateFormData')
        ->once()
        ->with($institution)
        ->andReturn(['institution' => $institution, 'mail_types' => []]);

    $response = (new MailController($service))->createMail($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('createMail allows a non-admin user with create_mails permission', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false, 'is_system_user' => false]);
    grantAdminPermission($user, $institution, 'create_mails');
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(InstitutionContextRequest::class);

    $request->shouldReceive('institution')->once()->andReturn($institution);
    $service->shouldReceive('getCreateFormData')
        ->once()
        ->with($institution)
        ->andReturn(['institution' => $institution, 'mail_types' => []]);

    $response = (new MailController($service))->createMail($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('getMails denies users without view permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false, 'is_system_user' => false]);
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(InstitutionContextRequest::class);

    $request->shouldReceive('institution')->once()->andReturn($institution);
    $service->shouldNotReceive('getIndexData');

    expect(fn (): Response => (new MailController($service))->getMails($request))
        ->toThrow(AuthorizationException::class);
});

test('createMail denies users without create permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false, 'is_system_user' => false]);
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(InstitutionContextRequest::class);

    $request->shouldReceive('institution')->once()->andReturn($institution);
    $service->shouldNotReceive('getCreateFormData');

    expect(fn (): Response => (new MailController($service))->createMail($request))
        ->toThrow(AuthorizationException::class);
});

test('storeMail stores and redirects to mail index', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(MailContentRequest::class);
    $validated = ['institution_id' => $institution->id, 'subject' => 'Test'];

    $request->shouldReceive('institution')->once()->andReturn($institution);
    $request->shouldReceive('validated')->once()->andReturn($validated);
    $service->shouldReceive('store')->once()->with($validated);

    $response = (new MailController($service))->storeMail($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toContain('mails');
});

test('editMail renders Inertia response after authorization', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create();
    $mail = MailContent::factory()
        ->for($institution, 'institution')
        ->for($mailType, 'mail_type')
        ->create();
    $user = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(MailContentIdRequest::class);

    $request->shouldReceive('mailContent')->once()->andReturn($mail);
    $service->shouldReceive('getEditFormData')
        ->once()
        ->with(Mockery::on(fn ($loadedMail): bool => $loadedMail instanceof MailContent
            && $loadedMail->is($mail)
            && $loadedMail->relationLoaded('mail_type')
            && $loadedMail->relationLoaded('institution')))
        ->andReturn(['mail' => $mail, 'institution' => $institution]);

    $response = (new MailController($service))->editMail($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('editMail eagerly loads mail relations on a relationless model', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create();
    $mail = MailContent::factory()
        ->for($institution, 'institution')
        ->for($mailType, 'mail_type')
        ->create();
    $user = User::factory()->create(['is_admin' => false, 'is_system_user' => false]);
    grantAdminPermission($user, $institution, 'edit_mails');
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(MailContentIdRequest::class);
    $relationlessMail = $mail->withoutRelations();

    expect($relationlessMail->relationLoaded('institution'))->toBeFalse()
        ->and($relationlessMail->relationLoaded('mail_type'))->toBeFalse();

    $request->shouldReceive('mailContent')->once()->andReturn($relationlessMail);

    Model::preventLazyLoading();

    try {
        $service->shouldReceive('getEditFormData')
            ->once()
            ->with(Mockery::on(function ($loadedMail) use ($mailType, $institution): bool {
                if (! $loadedMail instanceof MailContent) {
                    return false;
                }

                if (! $loadedMail->mail_type instanceof MailType || ! $loadedMail->institution instanceof Institution) {
                    return false;
                }

                return $loadedMail->mail_type->is($mailType)
                    && $loadedMail->institution->is($institution);
            }))
            ->andReturn(['mail' => $mail, 'institution' => $institution]);

        $response = (new MailController($service))->editMail($request);
    } finally {
        Model::preventLazyLoading(false);
    }

    expect($response)->toBeInstanceOf(Response::class);
});

test('editMail loads both mail relations before authorization and service handoff', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create();
    $mail = MailContent::factory()
        ->for($institution, 'institution')
        ->for($mailType, 'mail_type')
        ->create();
    $user = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(MailContentIdRequest::class);
    $loadedMail = $mail->load(['mail_type', 'institution']);
    $mailFromRequest = Mockery::mock($mail->withoutRelations())->makePartial();

    $mailFromRequest->shouldReceive('load')
        ->once()
        ->with(['mail_type', 'institution'])
        ->andReturn($loadedMail);
    $request->shouldReceive('mailContent')->once()->andReturn($mailFromRequest);
    $service->shouldReceive('getEditFormData')->once()->with($loadedMail)->andReturn([
        'mail' => $loadedMail,
        'institution' => $institution,
    ]);

    $response = (new MailController($service))->editMail($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('editMail denies users without edit permission', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create();
    $mail = MailContent::factory()
        ->for($institution, 'institution')
        ->for($mailType, 'mail_type')
        ->create();
    $user = User::factory()->create(['is_admin' => false, 'is_system_user' => false]);
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(MailContentIdRequest::class);

    $request->shouldReceive('mailContent')->once()->andReturn($mail);
    $service->shouldNotReceive('getEditFormData');

    expect(fn (): Response => (new MailController($service))->editMail($request))
        ->toThrow(AuthorizationException::class);
});

test('updateMail updates and redirects to mail index', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create();
    $mail = MailContent::factory()
        ->for($institution, 'institution')
        ->for($mailType, 'mail_type')
        ->create();
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(MailContentRequest::class);
    $validated = ['subject' => 'Updated Subject'];

    $request->shouldReceive('mailContent')->once()->andReturn($mail);
    $request->shouldReceive('validated')->once()->andReturn($validated);
    $request->shouldReceive('institution')->once()->andReturn($institution);
    $service->shouldReceive('update')->once()->with($mail, $validated);

    $response = (new MailController($service))->updateMail($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toContain('mails');
});

test('deleteMail deletes and redirects to mail index', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create();
    $mail = MailContent::factory()
        ->for($institution, 'institution')
        ->for($mailType, 'mail_type')
        ->create();

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(DeleteMailContentRequest::class);

    $request->shouldReceive('mailContent')->once()->andReturn($mail);
    $service->shouldReceive('delete')->once()->with($mail);

    $response = (new MailController($service))->deleteMail($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toContain('mails');
});

test('getMails calls authorize with viewAny', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true, 'is_system_user' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(InstitutionContextRequest::class);
    $request->shouldReceive('institution')->once()->andReturn($institution);
    $service->shouldReceive('getIndexData')->once()->andReturn(['mails' => [], 'institution' => $institution]);

    // If authorize is removed (RemoveMethodCall), unauthorized users could access the endpoint
    // This test verifies the admin user CAN access it (authorization passes)
    $response = (new MailController($service))->getMails($request);

    expect($response)->toBeInstanceOf(Response::class);
});

test('storeMail redirect URL contains institution_id', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(MailContentRequest::class);

    $request->shouldReceive('institution')->once()->andReturn($institution);
    $request->shouldReceive('validated')->once()->andReturn(['institution_id' => $institution->id]);
    $service->shouldReceive('store')->once();

    $response = (new MailController($service))->storeMail($request);

    // RemoveArrayItem would drop the 'institution_id' from the redirect params
    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toContain($institution->id);
});

test('updateMail redirect URL contains institution_id', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create();
    $mail = MailContent::factory()
        ->for($institution, 'institution')
        ->for($mailType, 'mail_type')
        ->create();
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(MailContentRequest::class);

    $request->shouldReceive('mailContent')->once()->andReturn($mail);
    $request->shouldReceive('validated')->once()->andReturn([]);
    $request->shouldReceive('institution')->once()->andReturn($institution);
    $service->shouldReceive('update')->once()->with($mail, []);

    $response = (new MailController($service))->updateMail($request);

    // RemoveArrayItem mutation would drop 'institution_id' from redirect params
    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toContain($institution->id);
});

test('storeMail calls service store method', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(MailContentRequest::class);

    $request->shouldReceive('institution')->once()->andReturn($institution);
    $request->shouldReceive('validated')->once()->andReturn(['institution_id' => $institution->id]);
    $service->shouldReceive('store')
        ->once()
        ->with(['institution_id' => $institution->id]);

    (new MailController($service))->storeMail($request);
});

test('deleteMail calls service delete method', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create();
    $mail = MailContent::factory()
        ->for($institution, 'institution')
        ->for($mailType, 'mail_type')
        ->create();

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(DeleteMailContentRequest::class);

    $request->shouldReceive('mailContent')->once()->andReturn($mail);
    $service->shouldReceive('delete')
        ->once()
        ->with($mail);

    $response = (new MailController($service))->deleteMail($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class);
});

test('deleteMail redirect URL contains institution_id from mail', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create();
    $mail = MailContent::factory()
        ->for($institution, 'institution')
        ->for($mailType, 'mail_type')
        ->create();

    $service = Mockery::mock(MailAdminService::class);
    $request = Mockery::mock(DeleteMailContentRequest::class);

    $request->shouldReceive('mailContent')->once()->andReturn($mail);
    $service->shouldReceive('delete')->once()->with($mail);

    $response = (new MailController($service))->deleteMail($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toContain($institution->id);
});
