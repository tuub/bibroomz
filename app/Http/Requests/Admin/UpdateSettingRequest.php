<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends SettingKeyRequest
{
    #[\Override]
    public function authorize(): bool
    {
        $user = $this->userModel();
        $settingable = $this->settingableOrNull();

        return $user instanceof User
            && ($settingable instanceof Institution || $settingable instanceof ResourceGroup)
            && $user->can('edit_settings', $settingable->institutionForSettings());
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function rules(): array
    {
        $settingableType = $this->inputString('settingable_type');
        $key = $this->inputString('key');

        $valueRules = Setting::getValidationRules(
            $settingableType,
            $key,
        );

        return array_merge(parent::rules(), [
            'key' => ['required', 'string', Rule::in(Setting::getDefinitionKeys($settingableType))],
            'value' => $valueRules,
        ]);
    }

    public function settingableOrNull(): Institution|ResourceGroup|null
    {
        $settingableType = $this->inputString('settingable_type');
        $settingableId = $this->inputString('settingable_id');

        if ($settingableType === null || $settingableId === null) {
            return null;
        }

        try {
            $model = Setting::getSettingableModel($settingableType);
        } catch (\InvalidArgumentException) {
            return null;
        }

        $settingable = $model->newQuery()->find($settingableId);

        return $settingable instanceof Institution || $settingable instanceof ResourceGroup
            ? $settingable
            : null;
    }
}
