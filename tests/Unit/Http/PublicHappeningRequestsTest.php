<?php

covers(
    App\Http\Requests\AddHappeningRequest::class,
    App\Http\Requests\CalendarEntriesRequest::class,
    App\Http\Requests\DeleteHappeningRequest::class,
    App\Http\Requests\UpdateHappeningRequest::class,
    App\Http\Requests\VerifyHappeningRequest::class
);

use App\Http\Requests\AddHappeningRequest;
use App\Http\Requests\CalendarEntriesRequest;
use App\Http\Requests\DeleteHappeningRequest;
use App\Http\Requests\UpdateHappeningRequest;
use App\Http\Requests\VerifyHappeningRequest;
use App\Library\Utility;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

function buildPublicHappeningFixture(array $happeningOverrides = []): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
        'is_active' => true,
    ]);
    $owner = User::factory()->create(['name' => 'owner.user']);
    $verifier = User::factory()->create(['name' => 'verifier.user']);
    $other = User::factory()->create(['name' => 'other.user']);

    $happening = Happening::create(array_merge([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => Utility::normalizeLoginName($verifier->name),
        'start' => CarbonImmutable::now()->addDay()->setTime(10, 0),
        'end' => CarbonImmutable::now()->addDay()->setTime(11, 0),
        'reserved_at' => CarbonImmutable::now()->addDay()->setTime(9, 0),
        'verified_at' => null,
        'label' => ['en' => 'Focus'],
    ], $happeningOverrides));

    return compact('institution', 'resourceGroup', 'resource', 'owner', 'verifier', 'other', 'happening');
}

test('calendar entries request validates slugs and parses the requested range', function () {
    $fixture = buildPublicHappeningFixture();

    $request = buildRoutedFormRequest(
        CalendarEntriesRequest::class,
        'GET',
        sprintf('/%s/%s/happenings', $fixture['institution']->slug, $fixture['resourceGroup']->slug),
        [
            'start' => '2026-06-04 08:00:00',
            'end' => '2026-06-04 18:00:00',
        ],
    );

    $validator = Validator::make($request->validationData(), $request->rules());
    $request->setValidator($validator);

    expect($validator->fails())->toBeFalse()
        ->and($request->resourceGroup()->is($fixture['resourceGroup']))->toBeTrue()
        ->and($request->startAt())->toBeInstanceOf(CarbonImmutable::class)
        ->and($request->endAt())->toBeInstanceOf(CarbonImmutable::class)
        ->and($request->startAt()->format('Y-m-d H:i:s'))->toBe('2026-06-04 08:00:00')
        ->and($request->endAt()->format('Y-m-d H:i:s'))->toBe('2026-06-04 18:00:00');
});

test('add happening request normalizes the verifier and exposes typed helpers', function () {
    $fixture = buildPublicHappeningFixture();

    $request = buildFormRequest(AddHappeningRequest::class, [
        'resource' => ['id' => $fixture['resource']->id],
        'start' => '2026-06-04 12:00:00',
        'end' => '2026-06-04 13:00:00',
        'verifier' => 'Verifier.User',
        'label' => ['en' => 'Workshop'],
    ], $fixture['owner']);

    $prepare = new ReflectionMethod($request, 'prepareForValidation');
    $prepare->setAccessible(true);
    $prepare->invoke($request);

    $validator = Validator::make($request->all(), $request->rules());

    expect($request->authorize())->toBeTrue()
        ->and($validator->fails())->toBeFalse()
        ->and($request->resource()->is($fixture['resource']))->toBeTrue()
        ->and($request->startAt()->format('Y-m-d H:i:s'))->toBe('2026-06-04 12:00:00')
        ->and($request->endAt()->format('Y-m-d H:i:s'))->toBe('2026-06-04 13:00:00')
        ->and($request->verifier())->toBe('verifier.user')
        ->and($request->label())->toBe(['en' => 'Workshop']);
});

test('add happening request requires a verifier when verification is required', function () {
    $fixture = buildPublicHappeningFixture();
    $request = buildFormRequest(AddHappeningRequest::class, [
        'resource' => ['id' => $fixture['resource']->id],
        'start' => '2026-06-04 12:00:00',
        'end' => '2026-06-04 13:00:00',
        'verifier' => '',
    ], $fixture['owner']);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->messages())->toHaveKey('verifier');
});

test('update delete and verify requests merge route ids and authorize the expected users', function () {
    $fixture = buildPublicHappeningFixture();

    $updateRequest = buildRoutedFormRequest(
        UpdateHappeningRequest::class,
        'POST',
        '/happening/update/' . $fixture['happening']->id,
        [
            'start' => '2026-06-04 12:00:00',
            'end' => '2026-06-04 13:00:00',
            'label' => ['en' => 'Updated'],
        ],
        $fixture['owner'],
    );
    $deleteRequest = buildRoutedFormRequest(
        DeleteHappeningRequest::class,
        'DELETE',
        '/happening/delete/' . $fixture['happening']->id,
        [],
        $fixture['owner'],
    );
    $verifyRequest = buildRoutedFormRequest(
        VerifyHappeningRequest::class,
        'POST',
        '/happening/verify/' . $fixture['happening']->id,
        [
            'start' => '2026-06-04 12:00:00',
            'end' => '2026-06-04 13:00:00',
        ],
        $fixture['verifier'],
    );

    expect($updateRequest->validationData()['id'])->toBe($fixture['happening']->id)
        ->and($deleteRequest->validationData()['id'])->toBe($fixture['happening']->id)
        ->and($verifyRequest->validationData()['id'])->toBe($fixture['happening']->id)
        ->and($updateRequest->authorize())->toBeTrue()
        ->and($deleteRequest->authorize())->toBeTrue()
        ->and($verifyRequest->authorize())->toBeTrue()
        ->and($updateRequest->happening()->is($fixture['happening']))->toBeTrue()
        ->and($deleteRequest->happening()->is($fixture['happening']))->toBeTrue()
        ->and($verifyRequest->happening()->is($fixture['happening']))->toBeTrue()
        ->and($updateRequest->label())->toBe(['en' => 'Updated'])
        ->and($verifyRequest->startAt()->format('Y-m-d H:i:s'))->toBe('2026-06-04 12:00:00');
});

test('update delete and verify requests reject unrelated users', function () {
    $fixture = buildPublicHappeningFixture();

    $updateRequest = buildRoutedFormRequest(
        UpdateHappeningRequest::class,
        'POST',
        '/happening/update/' . $fixture['happening']->id,
        [
            'start' => '2026-06-04 12:00:00',
            'end' => '2026-06-04 13:00:00',
        ],
        $fixture['other'],
    );
    $deleteRequest = buildRoutedFormRequest(
        DeleteHappeningRequest::class,
        'DELETE',
        '/happening/delete/' . $fixture['happening']->id,
        [],
        $fixture['other'],
    );
    $verifyRequest = buildRoutedFormRequest(
        VerifyHappeningRequest::class,
        'POST',
        '/happening/verify/' . $fixture['happening']->id,
        [
            'start' => '2026-06-04 12:00:00',
            'end' => '2026-06-04 13:00:00',
        ],
        $fixture['other'],
    );

    expect($updateRequest->authorize())->toBeFalse()
        ->and($deleteRequest->authorize())->toBeFalse()
        ->and($verifyRequest->authorize())->toBeFalse();
});
