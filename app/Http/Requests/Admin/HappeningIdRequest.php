<?php

namespace App\Http\Requests\Admin;

use App\Models\Happening;

class HappeningIdRequest extends AdminRouteRequest
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
            'id' => ['required', 'uuid', 'exists:happenings,id'],
        ];
    }

    public function happening(): Happening
    {
        return $this->findModelOrFail(Happening::class);
    }
}
