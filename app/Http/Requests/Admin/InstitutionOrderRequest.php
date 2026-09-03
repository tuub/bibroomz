<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Collection;

class InstitutionOrderRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();

        if (! $user instanceof User) {
            return false;
        }

        $institutions = Institution::query()
            ->whereIn('id', $this->rows()->pluck('id'))
            ->get()
            ->keyBy('id');

        foreach ($this->rows() as $row) {
            $institution = $institutions->get($row['id']);

            if ($institution === null || ! $user->can('update', $institution)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rows.*.id' => ['required', 'uuid', 'exists:institutions,id'],
            'rows.*.order' => ['required', 'integer'],
        ];
    }

    /**
     * @return Collection<int, array{id: string, order: int}>
     */
    public function rows(): Collection
    {
        $rows = $this->input('rows');

        return collect(is_array($rows) ? $rows : [])
            ->filter(fn (mixed $value): bool => is_array($value) && isset($value['id'], $value['order']))
            ->map(fn (array $value): array => [
                'id' => is_string($value['id']) ? $value['id'] : '',
                'order' => is_int($value['order'])
                    ? $value['order']
                    : (is_string($value['order']) && is_numeric($value['order']) ? (int) $value['order'] : 0),
            ])
            ->values();
    }
}
