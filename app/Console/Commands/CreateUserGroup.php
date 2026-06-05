<?php

namespace App\Console\Commands;

use App\Services\Console\CreateUserGroupAction;
use App\Services\Console\UserGroupInputCollector;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class CreateUserGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roomz:create-user-group';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a user group';

    public function __construct(
        private UserGroupInputCollector $inputCollector,
        private CreateUserGroupAction $createUserGroupAction,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        app()->setLocale('en');

        $input = $this->inputCollector->collect();

        try {
            $validated = $this->createUserGroupAction->validateInput($input->all());
        } catch (ValidationException $exception) {
            $this->renderValidationErrors($exception);

            return Command::FAILURE;
        }

        if (!$this->confirm('Are you sure you want to create this user group?')) {
            error('⚠ Cancelled.');

            return Command::INVALID;
        }

        $this->createUserGroupAction->execute($validated);

        info('User group created.');

        return Command::SUCCESS;
    }

    private function renderValidationErrors(ValidationException $exception): void
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
    }
}
