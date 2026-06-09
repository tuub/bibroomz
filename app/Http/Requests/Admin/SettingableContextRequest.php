<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

class SettingableContextRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'settingable_type' => ['required', 'string'],
            'settingable_id' => ['required', 'uuid'],
        ];
    }

    public function settingableType(): string
    {
        return $this->validatedString('settingable_type');
    }

    public function settingableId(): string
    {
        return $this->validatedString('settingable_id');
    }
}
