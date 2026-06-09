<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use App\Models\User;

class UpdateSettingRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();
        $setting = $this->settingOrNull();

        return $user instanceof User && $setting instanceof Setting && $user->can('edit', $setting);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'settingable_id' => ['required', 'uuid'],
            'settingable_type' => ['required', 'string'],
            'key' => ['required'],
            'value' => ['required'],
        ];
    }

    public function setting(): Setting
    {
        return $this->findModelOrFail(Setting::class);
    }

    public function settingOrNull(): ?Setting
    {
        return $this->findModel(Setting::class);
    }

    public function settingableId(): string
    {
        return $this->validatedString('settingable_id');
    }

    public function settingableType(): string
    {
        return $this->validatedString('settingable_type');
    }
}
