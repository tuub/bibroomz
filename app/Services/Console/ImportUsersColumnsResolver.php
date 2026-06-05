<?php

namespace App\Services\Console;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class ImportUsersColumnsResolver
{
    /**
     * @param resource $file
     * @param array<int, string> $modelKeys
     * @param array<int, string> $relationKeys
     * @return array<int, string>
     */
    public function resolve(
        $file,
        array $modelKeys,
        array $relationKeys,
        ?string $columnsOption,
        mixed $headerOption,
    ): array {
        $options = array_merge($modelKeys, $relationKeys);
        $columns = $this->parseColumns($file, $options, $modelKeys, $columnsOption, $headerOption);

        Validator::make(
            ['columns' => $columns],
            [
                'columns' => ['contains:' . implode(',', $modelKeys)],
                'columns.*' => 'string|in:' . implode(',', $options),
            ],
        )->validate();

        return $columns;
    }

    /**
     * @param resource $file
     * @param array<int, string> $options
     * @param array<int, string> $modelKeys
     * @return array<int, string>
     */
    private function parseColumns(
        $file,
        array $options,
        array $modelKeys,
        ?string $columnsOption,
        mixed $headerOption,
    ): array {
        if ($columnsOption) {
            if ($headerOption) {
                fgetcsv($file);
            }

            return array_values(array_filter(
                explode(',', $columnsOption),
                static fn (string $column): bool => $column !== '',
            ));
        }

        if ($headerOption || confirm('Does the file include a CSV header?')) {
            $header = fgetcsv($file);

            if (! is_array($header)) {
                return [];
            }

            return array_values(array_filter(
                $header,
                static fn (mixed $column): bool => is_string($column) && $column !== '',
            ));
        }

        $columns = [];

        for ($index = 0; $index < count($options); $index++) {
            if (Arr::sort(array_intersect($modelKeys, $columns)) === Arr::sort($modelKeys)) {
                if (!confirm('Does the file have additional columns?')) {
                    break;
                }
            }

            $selection = select(
                'Column ' . ($index + 1) . ':',
                options: array_values(array_diff($options, $columns)),
            );

            if (is_string($selection) && $selection !== '') {
                $columns[] = $selection;
            }
        }

        return $columns;
    }
}
