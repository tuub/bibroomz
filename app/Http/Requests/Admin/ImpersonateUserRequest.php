<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ImpersonateUserRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $targetUser = $this->findModel(User::class);
        $user = $this->userModel();

        if (! $user instanceof User || ! $targetUser instanceof Model) {
            return false;
        }

        if ($targetUser->is($user)) {
            return false;
        }

        if (session()->has('impersonator_id')) {
            return false;
        }

        return $user->can('impersonate', $targetUser);
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
