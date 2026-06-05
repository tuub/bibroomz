<?php

namespace App\Console\Commands;

use App\Services\Console\ImportUsersAction;
use App\Services\Console\ImportUsersColumnsResolver;
use App\Services\Console\ImportUsersCsvReader;
use App\Services\Console\ImportUsersDefaultsParser;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
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

    /**
     * @var list<string>
     */
    private array $modelKeys = ['name', 'email'];

    /**
     * @var list<string>
     */
    private array $relationKeys = ['valid_from', 'valid_until'];

    public function __construct(
        private ImportUsersDefaultsParser $defaultsParser,
        private ImportUsersColumnsResolver $columnsResolver,
        private ImportUsersCsvReader $csvReader,
        private ImportUsersAction $importUsersAction,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        app()->setLocale('en');

        $path = $this->argument('path') ?? text(
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
                $this->option('from'),
                $this->option('until'),
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
                $this->option('columns'),
                $this->option('header'),
            );
            $users = $this->csvReader->readAndValidate($file, $columns);
        } catch (ValidationException $exception) {
            fclose($file);

            return $this->handleValidationException($exception);
        }

        fclose($file);

        $group = $this->importUsersAction->resolveGroup($this->option('group'));
        $this->importUsersAction->execute($users, $defaults, $group);

        info('Import completed successfully.');

        return Command::SUCCESS;
    }

    private function handleValidationException(ValidationException $exception): int
    {
        foreach ($exception->errors() as $messages) {
            if (! is_array($messages)) {
                continue;
            }

            foreach ($messages as $message) {
                if (is_string($message)) {
                    error('⚠ ' . $message);
                }
            }
        }

        return Command::FAILURE;
    }
}
