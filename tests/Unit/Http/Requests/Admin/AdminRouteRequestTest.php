<?php

declare(strict_types=1);

use App\Http\Requests\Admin\AdminRouteRequest;
use App\Http\Requests\Admin\HappeningIdRequest;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(AdminRouteRequest::class);

uses(RefreshDatabase::class);

test('AdminRouteRequest is abstract class with validationData method', function (): void {
    $reflection = new ReflectionClass(AdminRouteRequest::class);

    expect($reflection->isAbstract())->toBeTrue()
        ->and($reflection->hasMethod('validationData'))->toBeTrue();
});

test('userModel returns null when no user is authenticated', function (): void {
    $request = new HappeningIdRequest;
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'userModel');

    expect($reflection->invoke($request))->toBeNull();
});

test('userModel returns the authenticated user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $request = buildAdminFormRequest(HappeningIdRequest::class, [], $user);
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'userModel');

    expect($reflection->invoke($request))->toBeInstanceOf(User::class);
});

test('userModel returns null when the resolved user is not an App user model', function (): void {
    $request = HappeningIdRequest::create('/', 'POST');
    $request->setUserResolver(fn (): stdClass => new stdClass);
    $request->setContainer(app());
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'userModel');

    expect($reflection->invoke($request))->toBeNull();
});

test('inputString returns null for empty string value', function (): void {
    $request = buildFormRequest(HappeningIdRequest::class, ['id' => '']);
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'inputString');

    expect($reflection->invoke($request, 'id'))->toBeNull();
});

test('inputString returns null for non-string value', function (): void {
    $request = buildFormRequest(HappeningIdRequest::class, ['id' => null]);
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'inputString');

    expect($reflection->invoke($request, 'id'))->toBeNull();
});

test('inputString returns string value when non-empty', function (): void {
    $request = buildFormRequest(HappeningIdRequest::class, ['id' => 'some-value']);
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'inputString');

    expect($reflection->invoke($request, 'id'))->toBe('some-value');
});

test('validationData returns only request input when no route is resolved', function (): void {
    $request = buildFormRequest(HappeningIdRequest::class, [
        0 => 'drop-me',
        'title' => 'Keep me',
    ]);

    expect($request->validationData())->toBe(['title' => 'Keep me']);
});

test('findModel returns null when id is not provided', function (): void {
    $request = new HappeningIdRequest;
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'findModel');

    expect($reflection->invoke($request, Happening::class))->toBeNull();
});

test('validatedString returns empty string when the validated value is not a string', function (): void {
    $request = buildFormRequest(HappeningIdRequest::class, ['id' => ['nested' => 'value']]);
    $validator = Validator::make($request->all(), ['id' => ['array']]);
    $validator->passes();
    $request->setValidator($validator);
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'validatedString');

    expect($reflection->invoke($request, 'id'))->toBe('');
});

test('findModel returns null without creating a query when the identifier is missing', function (): void {
    $modelClass = (new #[Table(name: 'users')] #[WithoutTimestamps] class extends Model
    {
        public function newQuery(): never
        {
            throw new RuntimeException('newQuery should not be called without an identifier.');
        }
    })::class;

    $request = buildFormRequest(HappeningIdRequest::class, ['id' => null]);
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'findModel');

    expect($reflection->invoke($request, $modelClass))->toBeNull();
});

test('findModel returns model when found', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $user->id,
        'is_verified' => false,
    ]);
    $request = buildFormRequest(HappeningIdRequest::class, ['id' => $happening->id]);
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'findModel');

    expect($reflection->invoke($request, Happening::class))->toBeInstanceOf(Happening::class);
});

test('findModel returns null when no record exists with the given id', function (): void {
    $request = buildFormRequest(HappeningIdRequest::class, ['id' => 'non-existent-id']);
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'findModel');

    expect($reflection->invoke($request, User::class))->toBeNull();
});

test('validationData merges route parameters and excludes numeric keys', function (): void {
    $request = buildRoutedFormRequest(HappeningIdRequest::class, 'GET', '/admin/happening/edit/route-uuid', [
        0 => 'drop-me',
        'title' => 'Keep me',
    ]);

    $data = $request->validationData();

    expect($data['id'])->toBe('route-uuid')
        ->and($data['title'])->toBe('Keep me')
        ->and($data)->not->toHaveKey(0);
});

test('findModelOrFail keeps the missing identifier in the thrown exception', function (): void {
    $request = buildFormRequest(HappeningIdRequest::class, ['id' => 'missing-id']);
    $validator = Validator::make($request->all(), ['id' => ['string']]);
    $validator->passes();
    $request->setValidator($validator);
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'findModelOrFail');

    try {
        $reflection->invoke($request, Happening::class);
        test()->fail('Expected ModelNotFoundException to be thrown.');
    } catch (ModelNotFoundException $exception) {
        expect($exception->getModel())->toBe(Happening::class)
            ->and($exception->getIds())->toBe(['missing-id']);
    }
});

test('findModelOrFail returns the found model when validation and lookup succeed', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $user->id,
        'is_verified' => false,
    ]);
    $request = buildFormRequest(HappeningIdRequest::class, ['id' => $happening->id]);
    $validator = Validator::make($request->all(), ['id' => ['string']]);
    $validator->passes();
    $request->setValidator($validator);
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'findModelOrFail');

    expect($reflection->invoke($request, Happening::class))->toBeInstanceOf(Happening::class);
});

test('normalizeStringKeyedArray excludes non-string keys', function (): void {
    $request = new HappeningIdRequest;
    $reflection = new ReflectionMethod(AdminRouteRequest::class, 'normalizeStringKeyedArray');

    $result = $reflection->invoke($request, [0 => 'numeric', 'key' => 'string', 1 => 'other']);

    expect($result)->toHaveKey('key')
        ->and($result)->not->toHaveKey(0)
        ->and($result)->not->toHaveKey(1);
});
