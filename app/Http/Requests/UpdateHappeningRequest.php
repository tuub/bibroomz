<?php

namespace App\Http\Requests;

use App\Models\Happening;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHappeningRequest extends FormRequest
{
    private ?Happening $happeningModel = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->happening()) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:happenings,id'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
            'label' => ['nullable'],
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

    public function startAt(): CarbonImmutable
    {
        $start = $this->input('start');

        return CarbonImmutable::parse(is_string($start) ? $start : null);
    }

    public function endAt(): CarbonImmutable
    {
        $end = $this->input('end');

        return CarbonImmutable::parse(is_string($end) ? $end : null);
    }

    public function label(): mixed
    {
        return $this->input('label');
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
