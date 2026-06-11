<?php

namespace App\Services\Console;

use App\Models\Institution;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;

class InstitutionInputCollector
{
    /**
     * @return Collection<string, mixed>
     */
    public function collectInstitutionInput(Command $command): Collection
    {
        $input = collect();

        info('Please enter the following information to create an institution:');

        $input->put('title', $this->translatableOption($command, 'title') ?? $this->translatableTextInput('Title'));
        $input->put('short_title', $command->option('short-title') ?? text('Short title', required: true));
        $input->put('slug', $command->option('slug') ?? text('Slug', required: true));
        $input->put('location', $command->option('location') ?? text('Location'));
        $input->put('home_uri', $command->option('home-uri') ?? text('Home URI'));
        $input->put('email', $command->option('email') ?? text('Email'));
        $input->put('logo_uri', $command->option('logo-uri') ?? text('Logo URI'));
        $input->put('teaser_uri', $command->option('teaser-uri') ?? text('Teaser URI'));
        $input->put('is_active', $this->booleanOption($command, 'active') ?? confirm('Active?'));
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
            ),
        );

        return $input;
    }

    /**
     * @return Collection<string, mixed>
     */
    public function collectResourceGroupInput(Institution $institution): Collection
    {
        info('Please enter the following information to create a resource group:');

        return collect()
            ->put('institution_id', $institution->id)
            ->put('title', $this->translatableTextInput('Name'))
            ->put('slug', text('Slug', required: true))
            ->put('term_singular', $this->translatableTextInput('Singular'))
            ->put('term_plural', $this->translatableTextInput('Plural'))
            ->put('description', $this->translatableTextInput('Description'))
            ->put('is_active', confirm('Active?'));
    }

    /**
     * @return array<string, string>
     */
    private function translatableTextInput(string $label): array
    {
        $input = [];

        foreach (['de', 'en'] as $lang) {
            $input[$lang] = text($label.' ('.$lang.')');
        }

        return $input;
    }

    /**
     * @return array<string, string>|null
     */
    private function translatableOption(Command $command, string $key): ?array
    {
        $option = $command->option($key);

        // Command options passed via --option=value are always strings when specified; this guard handles the unset/null case
        if (! is_string($option)) {
            return null;
        }

        return [app()->getLocale() => $option];
    }

    private function booleanOption(Command $command, string $key): ?bool
    {
        $option = $command->option($key);

        // Command options passed via --option=value are always strings when specified; this guard handles the unset/null case
        if (! is_string($option)) {
            return null;
        }

        return match ($option) {
            'true', 'yes', 'y' => true,
            default => false,
        };
    }
}
