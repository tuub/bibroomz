<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;

class InstitutionIdRequest extends AdminRouteRequest
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
            'id' => ['required', 'uuid', 'exists:institutions,id'],
        ];
    }

    public function institution(): Institution
    {
        return $this->findModelOrFail(Institution::class);
    }
}
