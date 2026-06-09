<?php

namespace App\Http\Requests;

use App\Models\Happening;
use Illuminate\Foundation\Http\FormRequest;

class DeleteHappeningRequest extends FormRequest
{
    private ?Happening $happeningModel = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->happening()) ?? false;
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

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function validationData(): array
    {
        return $this->normalizeStringKeyedArray(array_merge($this->all(), $this->route()?->parameters() ?? []));
    }

    public function happening(): Happening
    {
        if ($this->happeningModel instanceof Happening) {
            return $this->happeningModel;
        }

        $happeningId = $this->route('id') ?? $this->input('id');

        return $this->happeningModel = Happening::query()->findOrFail(is_string($happeningId) ? $happeningId : null);
    }

    /**
     * @param  array<mixed>  $values
     * @return array<string, mixed>
     */
    private function normalizeStringKeyedArray(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
