<?php

use App\Exceptions\HappeningValidationException;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Rules\RequiredWithTranslationRule;
use App\Rules\UniqueResourceGroupAttributeRule;
use App\Services\Admin\ClosableResolver;
use App\Services\AdminLoggingService;
use App\Services\Happenings\HappeningAudienceResolver;
use App\Services\Http\GetResourceTimeSlotsAction;
use App\Services\Http\ListPublicResourcesAction;
use App\Services\Http\ListUserHappeningsAction;
use App\Services\Http\RouteResourceGroupResolver;
use App\Services\Http\UserHappeningPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

covers(
    RequiredWithTranslationRule::class,
    UniqueResourceGroupAttributeRule::class,
    AdminLoggingService::class,
    ClosableResolver::class,
    HappeningAudienceResolver::class,
    RouteResourceGroupResolver::class,
    GetResourceTimeSlotsAction::class,
    ListPublicResourcesAction::class,
    ListUserHappeningsAction::class,
    UserHappeningPresenter::class
);

uses(RefreshDatabase::class);

test('required with translation rule fails only when every supported locale is empty', function (): void {
    $validator = Validator::make([
        'title' => ['de' => null, 'en' => 'Rooms'],
    ], [
        'title' => [new RequiredWithTranslationRule],
    ]);

    expect($validator->fails())->toBeFalse();

    $validator = Validator::make([
        'title' => ['de' => null, 'en' => null],
    ], [
        'title' => [new RequiredWithTranslationRule],
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->messages())->toHaveKey('title');
});

test('unique resource group attribute rule only fails on conflicting attributes inside the same institution', function (): void { // phpcs:ignore Generic.Files.LineLength
    $institution = Institution::factory()->create();
    $otherInstitution = Institution::factory()->create();
    $existing = ResourceGroup::factory()->create([
        'institution_id' => $institution->id,
        'slug' => 'rooms',
    ]);

    $errors = [];
    (new UniqueResourceGroupAttributeRule($institution->id, $existing->id))
        ->validate('slug', 'rooms', function ($message) use (&$errors): void {
            $errors[] = $message;
        });

    expect($errors)->toBe([]);

    (new UniqueResourceGroupAttributeRule($institution->id, 'another-group'))
        ->validate('slug', 'rooms', function ($message) use (&$errors): void {
            $errors[] = $message;
        });

    expect($errors)->toHaveCount(1);

    $errors = [];
    (new UniqueResourceGroupAttributeRule($otherInstitution->id, 'another-group'))
        ->validate('slug', 'rooms', function ($message) use (&$errors): void {
            $errors[] = $message;
        });

    expect($errors)->toBe([]);
});

test('admin logging service writes the expected admin log message', function (): void {
    $user = User::factory()->create();
    $institution = Institution::factory()->create();
    $this->actingAs($user);

    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once()->with(
        'user '.$user->id.' created '.$institution::class.' '.$institution->id
    );

    app(AdminLoggingService::class)->log('created', $institution);
});

test('happening validation exception stores translation data and renders its translated message', function (): void {
    $exception = new HappeningValidationException(
        'validation.after',
        ['date' => Utility::getTranslatable('Tomorrow')['en']]
    );

    expect($exception->getCode())->toBe(400)
        ->and($exception->translationKey)->toBe('validation.after')
        ->and($exception->context)->toHaveKey('date')
        ->and($exception->translatedMessage())->toBeString()
        ->and($exception->getMessage())->toBe($exception->translatedMessage());
});
