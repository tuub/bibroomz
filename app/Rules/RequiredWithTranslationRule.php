<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RequiredWithTranslationRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $languages = config('app.supported_locales');
        $translations = collect($value);

        foreach ($languages as $language) {
            if ($translations->get($language)) {
                return;
            }
        }

        $fail('validation.required')->translate();
    }
}
