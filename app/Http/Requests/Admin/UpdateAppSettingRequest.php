<?php

namespace App\Http\Requests\Admin;

use App\Models\AppSetting;

class UpdateAppSettingRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        return $this->userModel()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (AppSetting::getDefinitionKeys() as $key) {
            $rules[$key] = AppSetting::getValidationRules($key);
        }

        return $rules;
    }
}
