<?php

namespace App\Services\Console;

use App\Models\Institution;
use Illuminate\Support\Collection;

use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class UserGroupInputCollector
{
    /**
     * @return Collection<string, mixed>
     */
    public function collect(): Collection
    {
        info('Please enter the following information to create a user group:');
        $title = $this->translatableTextInput('Title');
        $options = $this->stringOptions(Institution::orderBy('title')->pluck('title', 'id')->all());
        $selection = select(
            label: 'Which institution does this user group belong to?',
            options: $options,
        );

        return collect()
            ->put('title', $title)
            ->put('institution_id', $this->resolveSelectedKey($selection, $options));
    }

    /**
     * @return array<string, string>
     */
    private function translatableTextInput(string $label): array
    {
        $input = [];

        foreach (['de', 'en'] as $lang) {
            $input[$lang] = text($label.' ('.$lang.')');
        }

        return $input;
    }

    /**
     * @param  array<int|string, string>  $options
     */
    private function resolveSelectedKey(mixed $selection, array $options): string
    {
        $selection = $this->normalizeSelection($selection);

        if (array_key_exists($selection, $options)) {
            return $selection;
        }

        $resolved = array_search($selection, $options, true);

        return is_string($resolved) || is_int($resolved) ? (string) $resolved : '';
    }

    /**
     * @param  array<mixed>  $options
     * @return array<int|string, string>
     */
    private function stringOptions(array $options): array
    {
        $normalized = [];

        foreach ($options as $key => $value) {
            if (is_string($value)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    private function normalizeSelection(mixed $selection): string
    {
        return is_string($selection) ? $selection : (is_scalar($selection) ? (string) $selection : '');
    }
}
