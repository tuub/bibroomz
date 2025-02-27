<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserGroup;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class ImportUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roomz:import-users {path?} {--from=} {--until=} {--columns=} {--header=} {--group=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from a file and add them to a user group';

    private array $modelKeys = ['name', 'email'];
    private array $relationKeys = ['valid_from', 'valid_until'];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app()->setLocale('en');

        $path = $this->argument('path') ?? text(
            'Where is the CSV file located?',
            default: 'users.csv',
            required: true,
        );

        if (!is_file($path) or !is_readable($path)) {
            error('⚠ File does not exist or is not readable.');

            return Command::FAILURE;
        }

        try {
            $defaults = $this->parseDefaults();
        } catch (InvalidFormatException $exception) {
            error('⚠ Invalid date format.');

            return Command::FAILURE;
        }

        $file = fopen($path, 'r');

        try {
            $columns = $this->parseAndValidateColumns($file);
            $users = $this->parseAndValidateUsers($file, $columns);
        } catch (ValidationException $exception) {
            return $this->handleValidationException($exception);
        }

        fclose($file);

        $this->addUsersToGroup($users, $defaults);

        info('Import completed successfully.');
    }

    private function parseDefaults(): array
    {
        $defaults = [];

        $from = $this->option('from');
        $until = $this->option('until');

        if ($from) {
            $defaults['valid_from'] = Carbon::parse($from)->format('Y-m-d');
        }

        if ($until) {
            $defaults['valid_until'] = Carbon::parse($until)->format('Y-m-d');
        }

        return $defaults;
    }

    private function parseColumns($file, array $options): array
    {
        if ($this->option('columns')) {
            if ($this->option('header')) {
                fgetcsv($file);
            }

            return explode(',', $this->option('columns'));
        }

        if ($this->option('header') || confirm('Does the file include a CSV header?')) {
            return fgetcsv($file);
        }

        $columns = [];

        for ($index = 0; $index < count($options); $index++) {
            if (Arr::sort(array_intersect($this->modelKeys, $columns)) === Arr::sort($this->modelKeys)) {
                if (!confirm('Does the file have additional columns?')) {
                    break;
                }
            }


            $columns[] = select('Column ' . $index + 1 . ':', options: array_values(array_diff($options, $columns)));
        }

        return $columns;
    }

    private function parseAndValidateColumns($file): array
    {
        $options = array_merge($this->modelKeys, $this->relationKeys);

        $columns = $this->parseColumns($file, $options);

        $this->validate(['columns' => $columns], [
            'columns' => ['contains:' . implode(',', $this->modelKeys)],
            'columns.*' => 'string|in:' . implode(',', $options),
        ]);

        return $columns;
    }

    private function parseUsers($file, array $columns): Collection
    {
        $users = collect();

        while ($line = fgetcsv($file)) {
            $user = [];

            foreach ($line as $index => $value) {
                $user[$columns[$index]] = trim($value);
            }

            $users->add($user);
        }

        return $users;
    }

    private function parseAndValidateUsers($file, array $columns): Collection
    {
        $users = $this->parseUsers($file, $columns);

        $this->validate(['users' => $users->toArray()], [
            'users' => ['list', 'min:1'],
            'users.*.name' => ['required', 'string'],
            'users.*.email' => ['required', 'string'],
            'users.*.valid_from' => ['filled', 'date'],
            'users.*.valid_until' => ['filled', 'date'],
        ]);

        return $users;
    }

    private function addUsersToGroup(Collection $users, array $defaults): void
    {
        $options = UserGroup::with('institution')->get()->sortBy('institution.title')->mapWithKeys(fn ($group) =>
             [$group->id => "{$group->title} ({$group->institution->title})"]);

        $group = UserGroup::findOrFail(
            $this->option('group') ?? select('Select a user group to add the users to:', $options)
        );

        foreach ($users as $user) {
            $model = User::firstOrCreate(Arr::only($user, $this->modelKeys), ['password' => Str::password()]);

            $attributes = Arr::only(array_merge($defaults, $user), $this->relationKeys);

            try {
                $group->users()->attach($model, $attributes);
            } catch (UniqueConstraintViolationException) {
                $group->users()->updateExistingPivot($model, $attributes);
            }
        }
    }

    private function handleValidationException(ValidationException $exception): int
    {
        foreach ($exception->errors() as $error) {
            foreach ($error as $message) {
                error('⚠ ' . $message);
            }
        }

        return Command::FAILURE;
    }

    private function validate($input, $rules): array
    {
        return Validator::make($input, $rules)->validate();
    }
}
