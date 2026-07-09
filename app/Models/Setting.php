<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Setting extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    use HasUuids;

    /*****************************************************************
     * OPTIONS
     ****************************************************************/
    protected $table = 'settings';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['key', 'value', 'settingable_type', 'settingable_id', 'institution_id'];

    /*****************************************************************
     * RELATIONS
     ****************************************************************/

    /**
     * @return MorphTo<Model, $this>
     */
    public function settingable(): MorphTo
    {
        return $this->morphTo();
    }

    /*****************************************************************
     * METHODS
     ****************************************************************/
    public static function getSettingableModel(string $settingableType): Institution|ResourceGroup
    {
        return match ($settingableType) {
            'institution', Institution::class => new Institution,
            'resource_group', ResourceGroup::class => new ResourceGroup,
            default => throw new \InvalidArgumentException("Unsupported settingable type [{$settingableType}]."),
        };
    }

    public function getInstitution(): Institution
    {
        $settingable = $this->settingable;
        assert($settingable instanceof Institution || $settingable instanceof ResourceGroup);

        return $settingable->institutionForSettings();
    }

    /**
     * @return array{
     *   institution: array<string, array{default: mixed, rules: array<int, mixed>, input_type: string}>,
     *   resource_group: array<string, array{default: mixed, rules: array<int, mixed>, input_type: string}>
     * }
     */
    public static function getDefinitions(): array
    {
        return [
            'institution' => [
                'timezone' => self::buildDefinition(config('roomz.default.timezone')),
                'date_format' => self::buildDefinition(config('roomz.default.date_format')),
                'time_format' => self::buildDefinition(config('roomz.default.time_format')),
                'cleanup_interval' => self::buildDefinition(config('roomz.default.cleanup_interval')),
                'allowed_ips' => self::buildDefinition(config('roomz.default.allowed_ips')),
                'system_notification' => self::buildDefinition('', ['nullable', 'string'], 'textarea'),
            ],
            'resource_group' => [
                'start_time_slot' => self::buildDefinition(config('roomz.default.start_time_slot')),
                'end_time_slot' => self::buildDefinition(config('roomz.default.end_time_slot')),
                'time_slot_length' => self::buildDefinition(config('roomz.default.timeslot_length')),
                'weeks_in_advance' => self::buildDefinition(config('roomz.default.weeks_in_advance')),
                'quota_weekly_happenings' => self::buildDefinition(config('roomz.default.quota.weekly_happenings')),
                'quota_daily_hours' => self::buildDefinition(config('roomz.default.quota.daily_hours')),
                'quota_weekly_hours' => self::buildDefinition(config('roomz.default.quota.weekly_hours')),
                'quota_happening_block_hours' => self::buildDefinition(config('roomz.default.quota.happening_block_hours')),
                'is_label_enabled' => self::buildDefinition(config('roomz.default.is_label_enabled')),
            ],
        ];
    }

    /**
     * @return array{
     *   institution: array<string, mixed>,
     *   resource_group: array<string, mixed>
     * }
     */
    public static function getInitialValues(): array
    {
        $initialValues = [
            'institution' => [],
            'resource_group' => [],
        ];

        foreach (self::getDefinitions() as $settingableType => $definitions) {
            foreach ($definitions as $key => $definition) {
                $initialValues[$settingableType][$key] = $definition['default'];
            }
        }

        return $initialValues;
    }

    /**
     * @return array<string, array{default: mixed, rules: array<int, mixed>, input_type: string}>
     */
    public static function getDefinitionsFor(?string $settingableType): array
    {
        $normalizedType = self::normalizeSettingableType($settingableType);

        if ($normalizedType === null) {
            return [];
        }

        return self::getDefinitions()[$normalizedType];
    }

    /**
     * @return list<string>
     */
    public static function getDefinitionKeys(?string $settingableType): array
    {
        return array_keys(self::getDefinitionsFor($settingableType));
    }

    public static function hasDefinition(?string $settingableType, ?string $key): bool
    {
        if (! is_string($key) || $key === '') {
            return false;
        }

        return array_key_exists($key, self::getDefinitionsFor($settingableType));
    }

    public static function getDefaultValue(?string $settingableType, ?string $key): mixed
    {
        return self::getDefinition($settingableType, $key)['default'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRules(?string $settingableType, ?string $key): array
    {
        return self::getDefinition($settingableType, $key)['rules'];
    }

    public static function getInputType(?string $settingableType, ?string $key): string
    {
        return self::getDefinition($settingableType, $key)['input_type'];
    }

    /**
     * @return array{default: mixed, rules: array<int, mixed>, input_type: string}
     */
    private static function getDefinition(?string $settingableType, ?string $key): array
    {
        if (! is_string($key) || $key === '') {
            return self::buildDefinition('');
        }

        $normalizedType = self::normalizeSettingableType($settingableType);

        if ($normalizedType === null) {
            return self::buildDefinition('');
        }

        return self::getDefinitions()[$normalizedType][$key] ?? self::buildDefinition('');
    }

    private static function normalizeSettingableType(?string $settingableType): ?string
    {
        return match ($settingableType) {
            'institution', Institution::class => 'institution',
            'resource_group', ResourceGroup::class => 'resource_group',
            default => null,
        };
    }

    /**
     * @param  array<int, mixed>  $rules
     * @return array{default: mixed, rules: array<int, mixed>, input_type: string}
     */
    private static function buildDefinition(mixed $default, array $rules = ['required'], string $inputType = 'text'): array
    {
        return [
            'default' => $default,
            'rules' => $rules,
            'input_type' => $inputType,
        ];
    }
}
