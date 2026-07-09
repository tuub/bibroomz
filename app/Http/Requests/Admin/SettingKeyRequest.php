<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

class SettingKeyRequest extends SettingableContextRequest
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'key' => ['required', 'string'],
        ]);
    }

    public function key(): string
    {
        return $this->validatedString('key');
    }
}
