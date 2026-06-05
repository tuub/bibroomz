<?php

namespace App\Http\Requests\Admin;

use App\Models\Closing;

class ClosingIdRequest extends AdminRouteRequest
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
            'id' => ['required', 'uuid', 'exists:closings,id'],
        ];
    }

    public function closing(): Closing
    {
        return $this->findModelOrFail(Closing::class);
    }
}
