<?php

namespace App\Services\Console;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class ImportUsersCsvReader
{
    /**
     * @param  resource  $file
     * @param  array<int, string>  $columns
     * @return Collection<int, array<string, string>>
     */
    public function readAndValidate($file, array $columns): Collection
    {
        $users = [];

        while (($line = fgetcsv($file)) !== false) {
            $user = [];

            foreach ($line as $index => $value) {
                $column = $columns[$index] ?? null;

                if (! is_string($column)) {
                    continue;
                }

                $user[$column] = trim($value ?? '');
            }

            $users[] = $user;
        }

        Validator::make(
            ['users' => $users],
            [
                'users' => ['list', 'min:1'],
                'users.*.name' => ['required', 'string'],
                'users.*.email' => ['required', 'string'],
                'users.*.valid_from' => ['filled', 'date'],
                'users.*.valid_until' => ['filled', 'date'],
            ],
        )->validate();

        return collect($users);
    }
}
