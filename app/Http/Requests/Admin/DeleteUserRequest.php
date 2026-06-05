<?php

namespace App\Http\Requests\Admin;

use App\Models\User;

class DeleteUserRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $targetUser = $this->findModel(User::class);
        $user = $this->userModel();

        return $targetUser !== null && $user !== null && $user->can('delete', $targetUser);
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
