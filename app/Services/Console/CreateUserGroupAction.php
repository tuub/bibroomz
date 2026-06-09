<?php

namespace App\Services\Console;

use App\Models\UserGroup;
use App\Rules\RequiredWithTranslationRule;
use Illuminate\Support\Facades\Validator;

class CreateUserGroupAction
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validateInput(array $input): array
    {
        return $this->stringKeyedArray(Validator::make(
            $input,
            [
                'title' => [new RequiredWithTranslationRule],
                'institution_id' => ['required', 'exists:institutions,id'],
            ],
        )->validate());
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function execute(array $validated): UserGroup
    {
        return UserGroup::create($validated);
    }

    /**
     * @param  array<mixed>  $values
     * @return array<string, mixed>
     */
    private function stringKeyedArray(array $values): array
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
