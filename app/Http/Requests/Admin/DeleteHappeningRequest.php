<?php

namespace App\Http\Requests\Admin;

use App\Models\Happening;

class DeleteHappeningRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $happening = $this->findModel(Happening::class);
        $user = $this->userModel();

        return $happening !== null && $user !== null && $user->can('adminDelete', $happening);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:happenings,id'],
        ];
    }

    public function happening(): Happening
    {
        return $this->findModelOrFail(Happening::class);
    }
}
