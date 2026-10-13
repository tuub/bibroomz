<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Console\ImportUsersAction;
use App\Services\Console\ImportUsersColumnsResolver;
use App\Services\Console\ImportUsersCsvReader;
use App\Services\Console\ImportUsersDefaultsParser;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

#[Description('Import users from a file and add them to a user group')]
#[Signature('roomz:import-users {path?} {--from=} {--until=} {--columns=} {--header=} {--group=}')]
class ImportUsers extends Command
{
    /**
     * @var list<string>
     */
    private array $modelKeys = ['name', 'email'];

    /**
     * @var list<string>
     */
    private array $relationKeys = ['valid_from', 'valid_until'];

    public function __construct(
        private readonly ImportUsersDefaultsParser $defaultsParser,
        private readonly ImportUsersColumnsResolver $columnsResolver,
        private readonly ImportUsersCsvReader $csvReader,
        private readonly ImportUsersAction $importUsersAction,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        app()->setLocale('en');

        $pathArgument = $this->argument('path');
        $path = is_string($pathArgument) ? $pathArgument : text(
            'Where is the CSV file located?',
            default: 'users.csv',
            required: true,
        );

        if (! is_file($path) || ! is_readable($path)) {
            error('⚠ File does not exist or is not readable.');

            return Command::FAILURE;
        }

        try {
            $defaults = $this->defaultsParser->parse(
                $this->stringOption('from'),
                $this->stringOption('until'),
            );
        } catch (InvalidFormatException $exception) {
            error('⚠ Invalid date format.');

            return Command::FAILURE;
        }

        $file = fopen($path, 'r');

        if (! is_resource($file)) {
            error('⚠ File could not be opened.');

            return Command::FAILURE;
        }

        try {
            $columns = $this->columnsResolver->resolve(
                $file,
                $this->modelKeys,
                $this->relationKeys,
                $this->stringOption('columns'),
                $this->option('header'),
            );
            $users = $this->csvReader->readAndValidate($file, $columns);
        } catch (ValidationException $exception) {
            fclose($file);

            return $this->handleValidationException($exception);
        }

        fclose($file);

        $group = $this->importUsersAction->resolveGroup($this->stringOption('group'));
        $this->importUsersAction->execute($users, $defaults, $group);

        info('Import completed successfully.');

        return Command::SUCCESS;
    }

    /**
     * Read an option that the signature declares with a value.
     *
     * A signature may also declare array and flag options, so the framework
     * types every option as `array|bool|float|int|string|null`. The ones this
     * command declares all take a value, which Symfony either fills with a
     * string or leaves null.
     */
    private function stringOption(string $key): ?string
    {
        $value = $this->option($key);

        return is_string($value) ? $value : null;
    }

    private function handleValidationException(ValidationException $exception): int
    {
        /** @var array<string, list<string>> $allErrors */
        $allErrors = $exception->errors();
        foreach ($allErrors as $messages) {
            foreach ($messages as $message) {
                error('⚠ '.$message);
            }
        }

        return Command::FAILURE;
    }
}
