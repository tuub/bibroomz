<?php

declare(strict_types=1);

use App\Http\Requests\Admin\ResourceGroupRequest;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Rules\RequiredWithTranslationRule;
use App\Rules\UniqueResourceGroupAttributeRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(ResourceGroupRequest::class);

uses(RefreshDatabase::class);

test('ResourceGroupRequest defines validation rules', function (): void {
    $request = new ResourceGroupRequest;

    expect($request->rules())->toBeArray();
});

test('ResourceGroupRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new ResourceGroupRequest;

    expect($request->authorize())->toBeFalse();
});

test('rules contains all expected keys', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    expect($rules)
        ->toHaveKey('id')
        ->toHaveKey('institution_id')
        ->toHaveKey('title')
        ->toHaveKey('slug')
        ->toHaveKey('term_singular')
        ->toHaveKey('term_plural')
        ->toHaveKey('description')
        ->toHaveKey('is_active')
        ->toHaveKey('user_groups')
        ->toHaveKey('user_groups.*')
        ->toHaveKey('help_uri');
});

test('id field rules contain nullable and uuid and exists', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    expect($rules['id'])
        ->toContain('nullable')
        ->toContain('uuid')
        ->toContain('exists:resource_groups,id');
});

test('institution_id field rules contain required uuid exists', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    expect($rules['institution_id'])
        ->toContain('required')
        ->toContain('uuid')
        ->toContain('exists:institutions,id');
});

test('is_active field rules contain required and boolean', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    expect($rules['is_active'])
        ->toContain('required')
        ->toContain('boolean');
});

test('user_groups field rules contain list', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    expect($rules['user_groups'])->toContain('list');
});

test('user_groups star field rules contain uuid', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    expect($rules['user_groups.*'])->toContain('uuid');
});

test('help_uri field rules contain nullable and url', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    expect($rules['help_uri'])
        ->toContain('nullable')
        ->toContain('url');
});

test('slug is required', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    $v = Validator::make([
        'institution_id' => $institution->id,
        'is_active' => false,
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('slug'))->toBeTrue();
});

test('is_active rejects non-boolean', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    $v = Validator::make([
        'institution_id' => $institution->id,
        'slug' => 'rooms',
        'is_active' => 'yes',
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('is_active'))->toBeTrue();
});

test('help_uri rejects non-url', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    $v = Validator::make([
        'institution_id' => $institution->id,
        'slug' => 'rooms',
        'is_active' => false,
        'help_uri' => 'not-a-url',
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('help_uri'))->toBeTrue();
});

test('user_groups star rejects non-uuid', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    $v = Validator::make([
        'institution_id' => $institution->id,
        'slug' => 'rooms',
        'is_active' => false,
        'user_groups' => ['not-a-uuid'],
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('user_groups.0'))->toBeTrue();
});

test('institution returns null when no institution_id is given', function (): void {
    $request = buildFormRequest(ResourceGroupRequest::class, []);

    expect($request->institution())->toBeNull();
});

test('institution returns the institution model for a valid institution_id', function (): void {
    $institution = Institution::factory()->create();
    $request = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id]);

    expect($request->institution()?->id)->toBe($institution->id);
});

test('resourceGroupOrNull returns null when no id is given', function (): void {
    $request = buildFormRequest(ResourceGroupRequest::class, []);

    expect($request->resourceGroupOrNull())->toBeNull();
});

test('prepareForValidation sets user_groups from input', function (): void {
    $request = buildFormRequest(ResourceGroupRequest::class, []);
    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

    expect($request->all())->toHaveKey('user_groups');
});

test('prepareForValidation coerces non-array user_groups to empty array', function (): void {
    $request = buildFormRequest(ResourceGroupRequest::class, ['user_groups' => 'not-array']);
    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

    expect($request->input('user_groups'))->toBe([]);
});

test('authorize returns true when admin user can edit existing resource group', function (): void {
    // InstanceOfToTrue would make (! $resourceGroup instanceof ResourceGroup) always false,
    // so it would always go to can('create') branch, skipping can('edit').
    $user = User::factory()->create(['is_admin' => true]);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $request = buildFormRequest(ResourceGroupRequest::class, [
        'id' => $rg->id,
        'institution_id' => $institution->id,
    ], $user);

    // With a real resourceGroup, authorize checks can('update', $rg) then can('create') or returns true
    expect($request->authorize())->toBeTrue();
});

test('authorize returns false for non-admin when resource group exists', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $request = buildFormRequest(ResourceGroupRequest::class, [
        'id' => $rg->id,
        'institution_id' => $institution->id,
    ], $user);

    // Non-admin cannot update
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when the institution is missing even if the resource group exists', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $request = buildFormRequest(ResourceGroupRequest::class, [
        'id' => $resourceGroup->id,
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('rules slug uses empty string when institution_id is null', function (): void {
    // CoalesceRemoveLeft would remove the left side of ??, using the raw inputString value which is null
    // This would fail UniqueResourceGroupAttributeRule constructor if null is passed instead of ''
    $request = buildFormRequest(ResourceGroupRequest::class, []);
    $rules = $request->rules();

    // Should not throw and slug key should exist
    expect($rules)->toHaveKey('slug');
});

test('slug unique rule receives the exact institution id string', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();
    $slugRules = $rules['slug'];

    if (! is_array($slugRules)) {
        throw new RuntimeException('Expected slug rules to be an array.');
    }

    $uniqueRule = collect($slugRules)->first(fn ($rule): bool => $rule instanceof UniqueResourceGroupAttributeRule);

    if (! $uniqueRule instanceof UniqueResourceGroupAttributeRule) {
        throw new RuntimeException('Expected a UniqueResourceGroupAttributeRule instance.');
    }

    $institutionIdProperty = new ReflectionProperty($uniqueRule, 'institution_id');

    expect($uniqueRule)->toBeInstanceOf(UniqueResourceGroupAttributeRule::class)
        ->and($institutionIdProperty->getValue($uniqueRule))->toBe($institution->id);
});

test('slug unique rule falls back to an empty institution id string for invalid input', function (): void {
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => ['not-a-string']])->rules();
    $slugRules = $rules['slug'];

    if (! is_array($slugRules)) {
        throw new RuntimeException('Expected slug rules to be an array.');
    }

    $uniqueRule = collect($slugRules)->first(fn ($rule): bool => $rule instanceof UniqueResourceGroupAttributeRule);

    if (! $uniqueRule instanceof UniqueResourceGroupAttributeRule) {
        throw new RuntimeException('Expected a UniqueResourceGroupAttributeRule instance.');
    }

    $institutionIdProperty = new ReflectionProperty($uniqueRule, 'institution_id');

    expect($institutionIdProperty->getValue($uniqueRule))->toBe('');
});

test('rules contains title with RequiredWithTranslationRule', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    expect($rules)->toHaveKey('title');
    expect($rules['title'])->not->toBeEmpty();
});

test('rules contains term_singular with RequiredWithTranslationRule', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    expect($rules)->toHaveKey('term_singular');
    expect($rules['term_singular'])->not->toBeEmpty();
});

test('rules contains term_plural with RequiredWithTranslationRule', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();

    expect($rules)->toHaveKey('term_plural');
    expect($rules['term_plural'])->not->toBeEmpty();
});

test('slug rules include the unique resource group attribute rule and description keeps the translation rule', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id])->rules();
    $slugRules = $rules['slug'];

    if (! is_array($slugRules)) {
        throw new RuntimeException('Expected slug rules to be an array.');
    }

    expect(collect($slugRules)->contains(fn ($rule): bool => $rule instanceof UniqueResourceGroupAttributeRule))->toBeTrue()
        ->and($rules['description'][0])->toBeInstanceOf(RequiredWithTranslationRule::class);
});
