<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwitchLanguageRequest extends FormRequest
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
            'locale' => ['required', 'in:en,de'],
        ];
    }

    public function locale(): string
    {
        $locale = $this->validated('locale');

        return is_string($locale) ? $locale : app()->getLocale();
    }
}
