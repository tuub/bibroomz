<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class CurrentPasswordRule implements ValidationRule
{
    public function __construct(private readonly ?string $name, private readonly ?string $current_password) {}

    public function validate(mixed $attribute, mixed $value, Closure $fail): void
    {
        $user = User::where('name', $this->name)->first();

        if (
            $user !== null && is_string($this->current_password)
            && ! Hash::check($this->current_password, $user->password)
        ) {
            $fail(trans('validation.current_password'));
        }
    }
}
