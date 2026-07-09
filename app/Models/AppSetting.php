<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    /**
     * @return array<string, array{default: mixed, rules: array<int, mixed>, input_type: string}>
     */
    public static function getDefinitions(): array
    {
        return [
            'system_notification' => self::buildDefinition('', ['nullable', 'string'], 'textarea'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function getDefinitionKeys(): array
    {
        return array_keys(self::getDefinitions());
    }

    public static function hasDefinition(?string $key): bool
    {
        return is_string($key) && $key !== '' && array_key_exists($key, self::getDefinitions());
    }

    public static function getDefaultValue(string $key): mixed
    {
        return self::getDefinitions()[$key]['default'] ?? null;
    }

    /**
     * @return array<int, mixed>
     */
    public static function getValidationRules(string $key): array
    {
        return self::getDefinitions()[$key]['rules'] ?? [];
    }

    public static function getInputType(string $key): string
    {
        return self::getDefinitions()[$key]['input_type'] ?? 'text';
    }

    public static function get(string $key): mixed
    {
        abort_unless(self::hasDefinition($key), 404);

        $appSetting = self::query()->find($key);

        return $appSetting instanceof self && $appSetting->value !== null
            ? $appSetting->value
            : self::getDefaultValue($key);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getCurrentValues(): array
    {
        $values = [];
        foreach (self::getDefinitionKeys() as $key) {
            $values[$key] = self::get($key);
        }

        return $values;
    }

    public static function set(string $key, mixed $value): self
    {
        abort_unless(self::hasDefinition($key), 404);

        return self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * @param  array<int, mixed>  $rules
     * @return array{default: mixed, rules: array<int, mixed>, input_type: string}
     */
    private static function buildDefinition(mixed $default, array $rules, string $inputType = 'text'): array
    {
        return ['default' => $default, 'rules' => $rules, 'input_type' => $inputType];
    }
}
