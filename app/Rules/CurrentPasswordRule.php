<?php

namespace App\Rules;

use Closure;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class CurrentPasswordRule implements ValidationRule
{
    public function __construct(private string|null $name, private string|null $current_password)
    {
    }

    public function validate(mixed $attribute, mixed $value, Closure $fail): void
    {
        $user = User::where('name', $this->name)->first();
        if ($user && !Hash::check($this->current_password, $user->password)) {
            $fail(trans('validation.current_password'));
        }
    }
}
