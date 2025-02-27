<?php

namespace App\Rules;

use App\Models\ResourceGroup;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueResourceGroupAttributeRule implements ValidationRule
{
    public function __construct(private string $institution_id, private string|null $resource_group_id)
    {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $existing = ResourceGroup::where([
            ['id', '!=', $this->resource_group_id],
            ['institution_id', '=', $this->institution_id],
            [$attribute, '=', $value]
        ])->first();

        if ($existing) {
            $fail(trans('validation.unique'));
        }
    }
}
