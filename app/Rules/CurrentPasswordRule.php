<?php

namespace App\Rules;

use Closure;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class CurrentPasswordRule implements ValidationRule
{
    private string|null $name;
    private string|null $current_password;

    public function __construct(string|null $name, string|null $current_password)
    {
        $this->name = $name;
        $this->current_password = $current_password;
    }

    public function validate(mixed $attribute, mixed $value, Closure $fail): void
    {
        $user = User::where('name', $this->name)->first();
        if ($user && !Hash::check($this->current_password, $user->password)) {
            $fail(trans('validation.current_password'));
        }
    }
}
