<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Rules\RequiredWithTranslationRule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;

class CreateInstitutionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'institution:create
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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $input = $this->promptUserForInstitutionInput();
        $this->validateInstitutionInput($input);

        if (!$this->confirm('Are you sure you want to create this institution?')) {
            error('⚠ Cancelled.');

            return Command::INVALID;
        }

        $institution = $this->createInstitutionFromInput($input);

        if (!$this->confirm('Do you want to create a resource group for this institution?')) {
            return Command::SUCCESS;
        }

        $this->createResourceGroupForInstitution($institution);
    }

    private function translatableTextInput($label)
    {
        $input = [];

        foreach (['de', 'en'] as $lang) {
            $input[$lang] = text($label . ' (' . $lang . ')');
        }

        return $input;
    }

    private function translatableOption($key)
    {
        if (!$this->option($key)) {
            return;
        }

        return array(app()->getLocale() => $this->option($key));
    }

    private function booleanOption($key)
    {
        if (!$this->option($key)) {
            return;
        }

        return match ($this->option($key)) {
            'true', 'yes', 'y' => true,
            default => false,
        };
    }

    private function promptUserForInstitutionInput()
    {
        $input = collect([]);

        info('Please enter the following information to create an institution:');

        $input->put('title', $this->translatableOption('title') ?? $this->translatableTextInput('Title'));
        $input->put('short_title', $this->option('short-title') ?? text('Short title', required: true));
        $input->put('slug', $this->option('slug') ?? text('Slug', required: true));
        $input->put('location', $this->option('location') ?? text('Location'));
        $input->put('home_uri', $this->option('home-uri') ?? text('Home URI'));
        $input->put('email', $this->option('email') ?? text('Email'));
        $input->put('logo_uri', $this->option('logo-uri') ?? text('Logo URI'));
        $input->put('teaser_uri', $this->option('teaser-uri') ?? text('Teaser URI'));
        $input->put('is_active', $this->booleanOption('active') ?? confirm('Active?'));

        $input->put(
            'week_days',
            multiselect(
                label: 'Active week days',
                options: [
                    '1' => 'Monday',
                    '2' => 'Tuesday',
                    '3' => 'Wednesday',
                    '4' => 'Thursday',
                    '5' => 'Friday',
                    '6' => 'Saturday',
                    '7' => 'Sunday',
                ],
                scroll: 7,
            )
        );

        return $input;
    }

    private function validate($validator)
    {
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                error('⚠ ' . $error);
            }

            // FIXME: don't use exit
            exit(Command::FAILURE);
        }
    }

    private function validateInstitutionInput($input)
    {
        $validator = Validator::make(
            $input->toArray(),
            [
                'title' => [new RequiredWithTranslationRule()],
                'short_title' => ['required'],
                'slug' => ['required', 'unique:institutions'],
                'location' => [],
                'week_days' => ['required_if:is_active,true'],
                'home_uri' => ['url'],
                'logo_uri' => ['url'],
                'teaser_uri' => ['url'],
                'email' => ['email'],
                'is_active' => ['required', 'boolean'],
            ]
        );

        $this->validate($validator);
    }

    private function createInstitutionFromInput($input): Institution
    {
        $institution = Institution::create($input->except('week_days')->toArray());
        $institution->week_days()->sync($input->get('week_days'));

        // FIXME: ask for input
        $settings = Setting::getInitialValues();
        foreach ($settings as $key => $value) {
            $setting = new Setting([
                'key' => $key,
                'value' => $value,
            ]);

            $institution->settings()->save($setting);
        }

        info('Institution created.');

        return $institution;
    }

    private function promptUserForResourceGroupInput(Institution $institution)
    {
        $input = collect([]);
        $input->put('institution_id', $institution->id);

        info('Please enter the following information to create a resource group:');

        $input->put('name', $this->translatableTextInput('Name'));
        $input->put('slug', text('Slug', required: true));
        $input->put('term_singular', $this->translatableTextInput('Singular'));
        $input->put('term_plural', $this->translatableTextInput('Plural'));
        $input->put('description', $this->translatableTextInput('Description'));
        $input->put('is_active', confirm('Active?'));

        return $input;
    }

    private function validateResourceGroupInput($input)
    {
        $validator = Validator::make(
            $input->toArray(),
            [
                'name' => [new RequiredWithTranslationRule()],
                'slug' => ['required'],
                'term_singular' => [new RequiredWithTranslationRule()],
                'term_plural' => [new RequiredWithTranslationRule()],
                'description' => [new RequiredWithTranslationRule()],
                'is_active' => ['required', 'boolean'],
            ]
        );

        $this->validate($validator);
    }

    private function createResourceGroupFromInput($input)
    {
        ResourceGroup::create($input->toArray());

        info('Resource group created.');
    }

    private function createResourceGroupForInstitution(Institution $institution)
    {
        $input = $this->promptUserForResourceGroupInput($institution);
        $this->validateResourceGroupInput($input);
        $this->createResourceGroupFromInput($input);
    }
}
