<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;

abstract class AdminRouteRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function validationData(): array
    {
        return $this->normalizeStringKeyedArray(array_merge($this->all(), $this->route()?->parameters() ?? []));
    }

    protected function userModel(): ?User
    {
        $user = $this->user();

        return $user instanceof User ? $user : null;
    }

    protected function inputString(string $key): ?string
    {
        $value = $this->input($key) ?? $this->route($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function validatedString(string $key): string
    {
        $value = $this->validated($key);

        return is_string($value) ? $value : '';
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return TModel|null
     */
    protected function findModel(string $modelClass, string $key = 'id'): ?Model
    {
        $identifier = $this->inputString($key);

        if ($identifier === null) {
            return null;
        }

        $model = new $modelClass;
        $found = $model->newQuery()->find($identifier);

        return $found instanceof $modelClass ? $found : null;
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return TModel
     */
    protected function findModelOrFail(string $modelClass, string $key = 'id'): Model
    {
        $identifier = $this->validatedString($key);
        $found = $this->findModel($modelClass, $key);

        if ($found instanceof $modelClass) {
            return $found;
        }

        throw (new ModelNotFoundException)->setModel($modelClass, [$identifier]);
    }

    /**
     * @param  array<mixed>  $values
     * @return array<string, mixed>
     */
    protected function normalizeStringKeyedArray(array $values): array
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
