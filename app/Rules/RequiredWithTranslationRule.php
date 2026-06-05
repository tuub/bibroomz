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

        if (! is_array($languages) || ! is_array($value)) {
            $fail('validation.required')->translate();

            return;
        }

        foreach ($languages as $language) {
            if (is_string($language) && ($value[$language] ?? null)) {
                return;
            }
        }

        $fail('validation.required')->translate();
    }
}
