<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;

class DeleteInstitutionRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $institution = $this->findModel(Institution::class);
        $user = $this->userModel();

        return $institution !== null && $user !== null && $user->can('delete', $institution);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:institutions,id'],
        ];
    }

    public function institution(): Institution
    {
        return $this->findModelOrFail(Institution::class);
    }
}
