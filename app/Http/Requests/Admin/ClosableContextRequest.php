<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

class ClosableContextRequest extends AdminRouteRequest
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
            'closable_type' => ['required', 'string'],
            'closable_id' => ['required', 'uuid'],
        ];
    }

    public function closableType(): string
    {
        return $this->validatedString('closable_type');
    }

    public function closableId(): string
    {
        return $this->validatedString('closable_id');
    }
}
