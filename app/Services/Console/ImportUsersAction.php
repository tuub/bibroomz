<?php

namespace App\Services\Console;

use App\Models\User;
use App\Models\UserGroup;
use App\Models\Institution;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;

class ImportUsersAction
{
    /**
     * @var array<int, string>
     */
    private array $modelKeys = ['name', 'email'];

    /**
     * @var array<int, string>
     */
    private array $relationKeys = ['valid_from', 'valid_until'];

    public function resolveGroup(?string $groupOption): UserGroup
    {
        if ($groupOption !== null && $groupOption !== '') {
            return UserGroup::findOrFail($groupOption);
        }

        $options = $this->groupOptions();

        return UserGroup::findOrFail(
            select('Select a user group to add the users to:', $options),
        );
    }

    /**
     * @param Collection<int, array<string, string>> $users
     * @param array<string, string> $defaults
     */
    public function execute(Collection $users, array $defaults, UserGroup $group): void
    {
        foreach ($users as $user) {
            $model = User::firstOrCreate(
                $this->extractAttributes($user, $this->modelKeys),
                ['password' => Str::password()],
            );

            $attributes = $this->extractAttributes(array_merge($defaults, $user), $this->relationKeys);

            try {
                $group->users()->attach($model, $attributes);
            } catch (UniqueConstraintViolationException) {
                $group->users()->updateExistingPivot($model, $attributes);
            }
        }
    }

    /**
     * @param array<string, string> $values
     * @param array<int, string> $keys
     * @return array<string, string>
     */
    private function extractAttributes(array $values, array $keys): array
    {
        /** @var array<string, string> $attributes */
        $attributes = Arr::only($values, $keys);

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    private function groupOptions(): array
    {
        $groups = UserGroup::with('institution')->get()->all();

        usort(
            $groups,
            fn (UserGroup $left, UserGroup $right): int => strcmp(
                $this->institutionTitle($left),
                $this->institutionTitle($right),
            ),
        );

        $options = [];

        foreach ($groups as $group) {
            $groupKey = $group->getKey();

            if ((! is_string($groupKey) && ! is_int($groupKey)) || ! $group->institution instanceof Institution) {
                continue;
            }

            $options[(string) $groupKey] = sprintf(
                '%s (%s)',
                $this->translatedTitle($group),
                $this->translatedTitle($group->institution),
            );
        }

        return $options;
    }

    private function institutionTitle(UserGroup $group): string
    {
        if (! $group->institution instanceof Institution) {
            return '';
        }

        return $this->translatedTitle($group->institution);
    }

    private function translatedTitle(UserGroup|Institution $model): string
    {
        $translation = $model->getTranslation('title', app()->getLocale());

        return is_string($translation) ? $translation : '';
    }
}
