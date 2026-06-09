<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UnbanUserRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $targetUser = $this->findModel(User::class);
        $user = $this->userModel();

        return $targetUser instanceof Model && $user instanceof User && $user->can('unban', $targetUser);
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
