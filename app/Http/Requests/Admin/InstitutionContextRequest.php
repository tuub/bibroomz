<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;

class InstitutionContextRequest extends AdminRouteRequest
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
            'institution_id' => ['required', 'uuid', 'exists:institutions,id'],
        ];
    }

    public function institution(): Institution
    {
        return $this->findModelOrFail(Institution::class, 'institution_id');
    }
}
