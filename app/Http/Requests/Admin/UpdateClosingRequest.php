<?php

namespace App\Http\Requests\Admin;

use App\Models\Closing;
use App\Models\User;

class UpdateClosingRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();
        $closing = $this->closingOrNull();

        return $user instanceof User && $closing instanceof Closing && $user->can('edit', $closing);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            'closable_id' => ['required', 'uuid'],
            'closable_type' => ['required', 'string'],
            'start_date' => ['required', 'date_format:d.m.Y'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date_format:d.m.Y'],
            'end_time' => ['required', 'date_format:H:i'],
            'description' => [''],
        ];
    }

    public function closing(): Closing
    {
        return $this->findModelOrFail(Closing::class);
    }

    public function closingOrNull(): ?Closing
    {
        return $this->findModel(Closing::class);
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
