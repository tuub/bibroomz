<?php

namespace App\Http\Requests\Admin;

use App\Models\Closing;

class DeleteClosingRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $closing = $this->findModel(Closing::class);
        $user = $this->userModel();

        return $closing !== null && $user !== null && $user->can('delete', $closing);
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
