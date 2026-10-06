<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Permissions\TypeScriptPermissionGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Generate the TypeScript permission registry')]
#[Signature('roomz:generate-permission-types
        {--check : Fail when the generated TypeScript registry is stale}')]
class GeneratePermissionTypesCommand extends Command
{
    public function __construct(private readonly TypeScriptPermissionGenerator $generator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('check')) {
            if ($this->generator->isCurrent()) {
                $this->components->info('The TypeScript permission registry is current.');

                return self::SUCCESS;
            }

            $this->components->error(
                'The TypeScript permission registry is stale. Run `php artisan roomz:generate-permission-types`.',
            );

            return self::FAILURE;
        }

        if ($this->generator->write()) {
            $this->components->info('Generated '.$this->generator->targetPath().'.');
        } else {
            $this->components->info('The TypeScript permission registry is already current.');
        }

        return self::SUCCESS;
    }
}
