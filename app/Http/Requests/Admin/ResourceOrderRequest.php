<?php

namespace App\Http\Requests\Admin;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Collection;

class ResourceOrderRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();

        if (! $user instanceof User) {
            return false;
        }

        $resources = Resource::query()
            ->whereIn('id', $this->rows()->pluck('id'))
            ->get()
            ->keyBy('id');

        foreach ($this->rows() as $row) {
            $resource = $resources->get($row['id']);

            if ($resource === null || ! $user->can('update', $resource)) {
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
            '*.id' => ['required', 'uuid', 'exists:resources,id'],
            '*.order' => ['required', 'integer'],
        ];
    }

    /**
     * @return Collection<int, array{id: string, order: int}>
     */
    public function rows(): Collection
    {
        return collect($this->all())
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
