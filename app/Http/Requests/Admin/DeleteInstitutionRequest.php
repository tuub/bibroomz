<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DeleteInstitutionRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $institution = $this->findModel(Institution::class);
        $user = $this->userModel();

        return $institution instanceof Model && $user instanceof User && $user->can('delete', $institution);
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
