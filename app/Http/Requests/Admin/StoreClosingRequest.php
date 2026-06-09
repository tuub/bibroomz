<?php

namespace App\Http\Requests\Admin;

use App\Models\Closing;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\User;
use App\Services\Admin\ClosableResolver;

class StoreClosingRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $closable = $this->closable();
        $user = $this->userModel();

        return $user instanceof User && $closable !== null && $user->can('create', [Closing::class, $closable]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'closable_id' => ['required', 'uuid'],
            'closable_type' => ['required'],
            'start_date' => ['required', 'date_format:d.m.Y'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date_format:d.m.Y'],
            'end_time' => ['required', 'date_format:H:i'],
            'description' => [''],
        ];
    }

    public function closable(): Institution|Resource|null
    {
        $type = $this->inputString('closable_type');
        $id = $this->inputString('closable_id');

        if ($type === null || $id === null) {
            return null;
        }

        return app(ClosableResolver::class)->resolve($type, $id);
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
