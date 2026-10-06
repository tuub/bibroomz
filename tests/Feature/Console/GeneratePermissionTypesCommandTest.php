<?php

declare(strict_types=1);

use App\Console\Commands\GeneratePermissionTypesCommand;
use App\Enums\PermissionKey;
use App\Services\Permissions\TypeScriptPermissionGenerator;
use Illuminate\Filesystem\Filesystem;
use Mockery\MockInterface;
use Symfony\Component\Console\Command\Command;

covers(GeneratePermissionTypesCommand::class, TypeScriptPermissionGenerator::class);

test('generated TypeScript contains every registered permission', function (): void {
    $generated = app(TypeScriptPermissionGenerator::class)->generate();

    foreach (PermissionKey::cases() as $permission) {
        expect($generated)->toContain("{$permission->name}: \"{$permission->value}\"");
    }
});

test('committed TypeScript permission registry is current', function (): void {
    $this->artisan('roomz:generate-permission-types', ['--check' => true])
        ->expectsOutputToContain('The TypeScript permission registry is current.')
        ->assertExitCode(Command::SUCCESS);
});

test('check mode fails when the committed registry is stale', function (): void {
    $files = Mockery::mock(Filesystem::class, function (MockInterface $mock): void {
        $mock->shouldReceive('exists')->once()->andReturnFalse();
    });
    app()->instance(TypeScriptPermissionGenerator::class, new TypeScriptPermissionGenerator($files));

    $this->artisan('roomz:generate-permission-types', ['--check' => true])
        ->expectsOutputToContain('The TypeScript permission registry is stale.')
        ->assertExitCode(Command::FAILURE);
});

test('generator writes a stale registry', function (): void {
    $files = Mockery::mock(Filesystem::class, function (MockInterface $mock): void {
        $mock->shouldReceive('exists')->once()->andReturnFalse();
        $mock->shouldReceive('put')
            ->once()
            ->with(resource_path('js/Types/PermissionKey.generated.ts'), Mockery::type('string'));
    });
    $generator = new TypeScriptPermissionGenerator($files);

    expect($generator->write())->toBeTrue();
});

test('generate mode does not rewrite a current registry', function (): void {
    $this->artisan('roomz:generate-permission-types')
        ->expectsOutputToContain('The TypeScript permission registry is already current.')
        ->assertExitCode(Command::SUCCESS);
});
