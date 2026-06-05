<?php

namespace App\Http\Requests\Admin;

use App\Models\User;

class UserIdRequest extends AdminRouteRequest
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
            'id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }

    public function targetUser(): User
    {
        return $this->findModelOrFail(User::class);
    }
}
