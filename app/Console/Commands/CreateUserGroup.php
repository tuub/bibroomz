<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Models\UserGroup;
use App\Rules\RequiredWithTranslationRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app()->setLocale('en');

        $input = $this->collectInput();

        try {
            $this->validate($input);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $error) {
                foreach ($error as $message) {
                    error('⚠ ' . $message);
                }
            }

            return Command::FAILURE;
        }

        if (!$this->confirm('Are you sure you want to create this user group?')) {
            error('⚠ Cancelled.');

            return Command::INVALID;
        }

        UserGroup::create($input->toArray());

        info('User group created.');
    }

    private function translatableTextInput($label)
    {
        $input = [];

        foreach (['de', 'en'] as $lang) {
            $input[$lang] = text($label . ' (' . $lang . ')');
        }

        return $input;
    }

    private function collectInput()
    {
        info('Please enter the following information to create a user group:');

        return collect()
            ->put('title', $this->translatableTextInput('Title'))
            ->put(
                'institution_id',
                select(
                    label: 'Which institution does this user group belong to?',
                    options: Institution::orderBy('title')->pluck('title', 'id')
                )
            );
    }

    private function validate($input): array
    {
        return Validator::make(
            $input->toArray(),
            [
                'title' => [new RequiredWithTranslationRule()],
                'institution_id' => ['required', 'exists:institutions,id'],
            ]
        )->validate();
    }
}
