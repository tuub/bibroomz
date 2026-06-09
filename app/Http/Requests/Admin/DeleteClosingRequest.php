<?php

namespace App\Http\Requests\Admin;

use App\Models\Closing;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DeleteClosingRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $closing = $this->findModel(Closing::class);
        $user = $this->userModel();

        return $closing instanceof Model && $user instanceof User && $user->can('delete', $closing);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:closings,id'],
        ];
    }

    public function closing(): Closing
    {
        return $this->findModelOrFail(Closing::class);
    }
}
