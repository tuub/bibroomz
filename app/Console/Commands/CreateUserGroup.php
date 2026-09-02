<?php

namespace App\Console\Commands;

use App\Services\Console\CreateUserGroupAction;
use App\Services\Console\UserGroupInputCollector;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

#[Description('Create a user group')]
#[Signature('roomz:create-user-group')]
class CreateUserGroup extends Command
{
    public function __construct(
        private readonly UserGroupInputCollector $inputCollector,
        private readonly CreateUserGroupAction $createUserGroupAction,
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

        if (! $this->confirm('Are you sure you want to create this user group?')) {
            error('⚠ Cancelled.');

            return Command::INVALID;
        }

        $this->createUserGroupAction->execute($validated);

        info('User group created.');

        return Command::SUCCESS;
    }

    private function renderValidationErrors(ValidationException $exception): void
    {
        /** @var array<string, list<string>> $allErrors */
        $allErrors = $exception->errors();
        foreach ($allErrors as $messages) {
            foreach ($messages as $message) {
                error('⚠ '.$message);
            }
        }
    }
}
