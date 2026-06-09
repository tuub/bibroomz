<?php

namespace App\Console\Commands;

use App\Services\Console\CreateInstitutionAction;
use App\Services\Console\CreateInstitutionResourceGroupAction;
use App\Services\Console\InstitutionInputCollector;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class CreateInstitutionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roomz:create-institution
        {--title= : The title of the institution}
        {--short-title= : The short title of the institution}
        {--slug= : The slug of the institution}
        {--location= : The location of the institution}
        {--home-uri= : The home URI of the institution}
        {--email= : The email of the institution}
        {--logo-uri= : The logo URI of the institution}
        {--teaser-uri= : The teaser URI of the institution}
        {--active= : Whether the institution is active}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an institution';

    public function __construct(
        private readonly InstitutionInputCollector $inputCollector,
        private readonly CreateInstitutionAction $createInstitutionAction,
        private readonly CreateInstitutionResourceGroupAction $createInstitutionResourceGroupAction,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $input = $this->inputCollector->collectInstitutionInput($this);

        try {
            $validatedInstitution = $this->createInstitutionAction->validateInput($input->all());
        } catch (ValidationException $exception) {
            $this->renderValidationErrors($exception);

            return Command::FAILURE;
        }

        if (! $this->confirm('Are you sure you want to create this institution?')) {
            error('⚠ Cancelled.');

            return Command::INVALID;
        }

        $institution = $this->createInstitutionAction->execute($validatedInstitution);
        info('Institution created.');

        if (! $this->confirm('Do you want to create a resource group for this institution?')) {
            return Command::SUCCESS;
        }

        $resourceGroupInput = $this->inputCollector->collectResourceGroupInput($institution);

        try {
            $validatedResourceGroup = $this->createInstitutionResourceGroupAction
                ->validateInput($resourceGroupInput->all());
        } catch (ValidationException $exception) {
            $this->renderValidationErrors($exception);

            return Command::FAILURE;
        }

        $this->createInstitutionResourceGroupAction->execute($validatedResourceGroup);
        info('Resource group created.');

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
                    error('⚠ '.$message);
                }
            }
        }
    }
}
