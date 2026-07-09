<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(Setting::class);

uses(RefreshDatabase::class);

test('setting getInitialValues returns institution and resource_group keys', function (): void {
    $values = Setting::getInitialValues();

    expect($values)->toHaveKey('institution')
        ->and($values)->toHaveKey('resource_group');
});

test('setting getInitialValues institution contains all expected keys', function (): void {
    $values = Setting::getInitialValues();

    expect($values['institution'])->toHaveKeys([
        'timezone',
        'date_format',
        'time_format',
        'cleanup_interval',
        'allowed_ips',
        'system_notification',
    ]);
});

test('setting getInitialValues resource_group contains all expected keys', function (): void {
    $values = Setting::getInitialValues();

    expect($values['resource_group'])->toHaveKeys([
        'start_time_slot',
        'end_time_slot',
        'time_slot_length',
        'weeks_in_advance',
        'quota_weekly_happenings',
        'quota_daily_hours',
        'quota_weekly_hours',
        'quota_happening_block_hours',
        'is_label_enabled',
    ]);
});

test('setting getValidationRules returns configured rules for known setting keys', function (): void {
    expect(Setting::getValidationRules('institution', 'system_notification'))->toBe(['nullable', 'string'])
        ->and(Setting::getValidationRules('institution', 'timezone'))->toBe(['required']);
});

test('setting exposes definition helpers for known keys', function (): void {
    expect(Setting::getDefinitionKeys('institution'))->toContain('timezone', 'system_notification')
        ->and(Setting::hasDefinition('institution', 'timezone'))->toBeTrue()
        ->and(Setting::hasDefinition('institution', 'missing_key'))->toBeFalse()
        ->and(Setting::getDefaultValue('institution', 'system_notification'))->toBe('');
});

test('setting getInputType returns configured input type for known setting keys', function (): void {
    expect(Setting::getInputType('institution', 'system_notification'))->toBe('textarea')
        ->and(Setting::getInputType('institution', 'timezone'))->toBe('text');
});

test('setting getSettingableModel returns Institution for institution type', function (): void {
    $model = Setting::getSettingableModel('institution');

    expect($model)->toBeInstanceOf(Institution::class);
});

test('setting getSettingableModel returns Institution for Institution class type', function (): void {
    $model = Setting::getSettingableModel(Institution::class);

    expect($model)->toBeInstanceOf(Institution::class);
});

test('setting getSettingableModel returns ResourceGroup for resource_group type', function (): void {
    $model = Setting::getSettingableModel('resource_group');

    expect($model)->toBeInstanceOf(ResourceGroup::class);
});

test('setting getSettingableModel returns ResourceGroup for ResourceGroup class type', function (): void {
    $model = Setting::getSettingableModel(ResourceGroup::class);

    expect($model)->toBeInstanceOf(ResourceGroup::class);
});

test('setting getSettingableModel throws for unknown type', function (): void {
    expect(fn (): Institution|\App\Models\ResourceGroup => Setting::getSettingableModel('unknown'))->toThrow(InvalidArgumentException::class);
});

test('setting belongs to institution via morphTo', function (): void {
    $institution = Institution::factory()->create();
    $setting = new Setting(['key' => 'timezone', 'value' => 'UTC']);
    $institution->settings()->save($setting);

    expect($setting->settingable)->toBeInstanceOf(Institution::class);
});

test('setting belongs to resource group via morphTo', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $setting = new Setting(['key' => 'start_time_slot', 'value' => '08:00']);
    $rg->settings()->save($setting);

    expect($setting->settingable)->toBeInstanceOf(ResourceGroup::class);
});

test('setting getInstitution returns the institution when settingable is Institution', function (): void {
    $institution = Institution::factory()->create();
    $setting = new Setting(['key' => 'timezone', 'value' => 'UTC']);
    $institution->settings()->save($setting);
    $setting->load('settingable');

    expect($setting->getInstitution())->toBeInstanceOf(Institution::class)
        ->and($setting->getInstitution()->id)->toBe($institution->id);
});

test('setting getInstitution returns institution through resource group', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $setting = new Setting(['key' => 'start_time_slot', 'value' => '08:00']);
    $rg->settings()->save($setting);
    $setting->load('settingable.institution');

    expect($setting->getInstitution())->toBeInstanceOf(Institution::class)
        ->and($setting->getInstitution()->id)->toBe($institution->id);
});

test('setting getInstitution asserts when settingable relation has an unsupported model type', function (): void {
    $institution = Institution::factory()->create();
    $setting = new Setting(['key' => 'timezone', 'value' => 'UTC']);
    $invalidSettingable = new class extends Model
    {
        public Institution $institution;

        public function institutionForSettings(): Institution
        {
            return $this->institution;
        }
    };
    $invalidSettingable->institution = $institution;

    $setting->setRelation('settingable', $invalidSettingable);

    expect(fn (): Institution => $setting->getInstitution())->toThrow(AssertionError::class);
});

test('setting has no timestamps', function (): void {
    $setting = new Setting;

    expect($setting->timestamps)->toBeFalse();
});

test('setting does not auto-increment', function (): void {
    $setting = new Setting;

    expect($setting->incrementing)->toBeFalse();
});
