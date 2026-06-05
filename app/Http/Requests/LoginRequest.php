<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'username' => ['required'],
            'password' => ['required'],
        ];
    }

    /**
     * @return array{username: string, password: string}
     */
    public function credentials(): array
    {
        $username = $this->input('username');
        $password = $this->input('password');

        return [
            'username' => is_string($username) ? $username : '',
            'password' => is_string($password) ? $password : '',
        ];
    }
}
