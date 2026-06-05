<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;

class SettingIdRequest extends AdminRouteRequest
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
            'id' => ['required', 'uuid', 'exists:settings,id'],
        ];
    }

    public function setting(): Setting
    {
        return $this->findModelOrFail(Setting::class);
    }
}
