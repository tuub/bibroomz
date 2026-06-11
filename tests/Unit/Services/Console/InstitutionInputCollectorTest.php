<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Services\Console\InstitutionInputCollector;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\MultiSelectPrompt;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\TextPrompt;
use Symfony\Component\Console\Output\BufferedOutput;

covers(InstitutionInputCollector::class);

uses(RefreshDatabase::class);

/**
 * @param  list<string>  $textResponses
 * @param  list<list<string>>  $multiselectResponses
 * @param  list<bool>  $confirmResponses
 */
function usePromptFallback(array $textResponses = [], array $multiselectResponses = [], array $confirmResponses = []): void
{
    TextPrompt::fallbackUsing(function (TextPrompt $prompt) use (&$textResponses): string {
        return array_shift($textResponses) ?? '';
    });

    MultiSelectPrompt::fallbackUsing(function (MultiSelectPrompt $prompt) use (&$multiselectResponses): array {
        return array_shift($multiselectResponses) ?? [];
    });

    ConfirmPrompt::fallbackUsing(function (ConfirmPrompt $prompt) use (&$confirmResponses): bool {
        return array_shift($confirmResponses) ?? false;
    });

    $reflection = new ReflectionClass(Prompt::class);
    $property = $reflection->getProperty('shouldFallback');
    $property->setValue(true);
}

function resetPromptFallback(): void
{
    $reflection = new ReflectionClass(Prompt::class);
    $property = $reflection->getProperty('shouldFallback');
    $property->setValue(false);
}

beforeEach(function (): void {
    resetPromptFallback();
});

afterEach(function (): void {
    resetPromptFallback();
});

test('institution input collector booleanOption returns true for yes values', function (): void {
    $collector = new InstitutionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('booleanOption');

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('active')->andReturn('yes');

    expect($method->invoke($collector, $command, 'active'))->toBeTrue();
});

test('institution input collector booleanOption returns false for other values', function (): void {
    $collector = new InstitutionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('booleanOption');

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('active')->andReturn('no');

    expect($method->invoke($collector, $command, 'active'))->toBeFalse();
});

test('institution input collector booleanOption returns null when option not provided', function (): void {
    $collector = new InstitutionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('booleanOption');

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('active')->andReturn(null);

    expect($method->invoke($collector, $command, 'active'))->toBeNull();
});

test('booleanOption returns true for "true" string', function (): void {
    $collector = new InstitutionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('booleanOption');

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('active')->andReturn('true');

    expect($method->invoke($collector, $command, 'active'))->toBeTrue();
});

test('booleanOption returns true for "y" string', function (): void {
    $collector = new InstitutionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('booleanOption');

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('active')->andReturn('y');

    expect($method->invoke($collector, $command, 'active'))->toBeTrue();
});

test('booleanOption returns false for arbitrary string value', function (): void {
    $collector = new InstitutionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('booleanOption');

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('active')->andReturn('maybe');

    expect($method->invoke($collector, $command, 'active'))->toBeFalse();
});

test('translatableOption returns keyed array when option is a string', function (): void {
    $collector = new InstitutionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('translatableOption');

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('title')->andReturn('My Title');

    $result = $method->invoke($collector, $command, 'title');

    expect($result)->toBeArray()
        ->and($result)->toHaveKey(app()->getLocale())
        ->and($result[app()->getLocale()])->toBe('My Title');
});

test('translatableOption returns null when option is not a string', function (): void {
    $collector = new InstitutionInputCollector;
    $reflection = new ReflectionClass($collector);
    $method = $reflection->getMethod('translatableOption');

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('title')->andReturn(null);

    expect($method->invoke($collector, $command, 'title'))->toBeNull();
});

// --- Mutation-killing tests ---

test('collectInstitutionInput puts all required keys into collection (lines 26-32 RemoveMethodCall)', function (): void {
    $collector = new InstitutionInputCollector;

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('title')->andReturn('My Title');
    $command->shouldReceive('option')->with('short-title')->andReturn('MT');
    $command->shouldReceive('option')->with('slug')->andReturn('my-title');
    $command->shouldReceive('option')->with('location')->andReturn('Berlin');
    $command->shouldReceive('option')->with('home-uri')->andReturn('https://example.com');
    $command->shouldReceive('option')->with('email')->andReturn('info@example.com');
    $command->shouldReceive('option')->with('logo-uri')->andReturn('https://example.com/logo.png');
    $command->shouldReceive('option')->with('teaser-uri')->andReturn('https://example.com/teaser.png');
    $command->shouldReceive('option')->with('active')->andReturn('yes');

    usePromptFallback(multiselectResponses: [[]]);

    $result = $collector->collectInstitutionInput($command);

    expect($result->has('title'))->toBeTrue()
        ->and($result->has('short_title'))->toBeTrue()
        ->and($result->has('slug'))->toBeTrue()
        ->and($result->has('location'))->toBeTrue()
        ->and($result->has('home_uri'))->toBeTrue()
        ->and($result->has('email'))->toBeTrue()
        ->and($result->has('logo_uri'))->toBeTrue()
        ->and($result->has('teaser_uri'))->toBeTrue()
        ->and($result->has('is_active'))->toBeTrue()
        ->and($result->has('week_days'))->toBeTrue();
});

test('collectInstitutionInput week_days multiselect has 7 day options (lines 39-45 RemoveArrayItem)', function (): void {
    $collector = new InstitutionInputCollector;

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('title')->andReturn('T');
    $command->shouldReceive('option')->with('short-title')->andReturn('T');
    $command->shouldReceive('option')->with('slug')->andReturn('t');
    $command->shouldReceive('option')->with('location')->andReturn('l');
    $command->shouldReceive('option')->with('home-uri')->andReturn('h');
    $command->shouldReceive('option')->with('email')->andReturn('e');
    $command->shouldReceive('option')->with('logo-uri')->andReturn('lo');
    $command->shouldReceive('option')->with('teaser-uri')->andReturn('te');
    $command->shouldReceive('option')->with('active')->andReturn('yes');

    usePromptFallback(multiselectResponses: [[
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ]]);

    $result = $collector->collectInstitutionInput($command);

    // All 7 days should be in week_days when all selected
    /** @var array<string> $weekDays */
    $weekDays = $result->get('week_days');
    expect($weekDays)->not->toBeEmpty()
        ->and(count($weekDays))->toBe(7);
});

test('collectResourceGroupInput puts all required keys', function (): void {
    $collector = new InstitutionInputCollector;
    $institution = Institution::factory()->create();

    usePromptFallback(
        textResponses: [
            'Name DE',
            'Name EN',
            'slug-val',
            'Singular DE',
            'Singular EN',
            'Plural DE',
            'Plural EN',
            'Description DE',
            'Description EN',
        ],
        confirmResponses: [true],
    );

    $result = $collector->collectResourceGroupInput($institution);

    expect($result->has('institution_id'))->toBeTrue()
        ->and($result->get('institution_id'))->toBe($institution->id)
        ->and($result->has('title'))->toBeTrue()
        ->and($result->has('slug'))->toBeTrue()
        ->and($result->has('term_singular'))->toBeTrue()
        ->and($result->has('term_plural'))->toBeTrue()
        ->and($result->has('description'))->toBeTrue()
        ->and($result->has('is_active'))->toBeTrue();
});

test('collectInstitutionInput outputs info message', function (): void {
    $collector = new InstitutionInputCollector;

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('title')->andReturn('T');
    $command->shouldReceive('option')->with('short-title')->andReturn('T');
    $command->shouldReceive('option')->with('slug')->andReturn('t');
    $command->shouldReceive('option')->with('location')->andReturn('l');
    $command->shouldReceive('option')->with('home-uri')->andReturn('h');
    $command->shouldReceive('option')->with('email')->andReturn('e@e.e');
    $command->shouldReceive('option')->with('logo-uri')->andReturn('lo');
    $command->shouldReceive('option')->with('teaser-uri')->andReturn('te');
    $command->shouldReceive('option')->with('active')->andReturn('yes');

    usePromptFallback(multiselectResponses: [[]]);

    $buffered = new BufferedOutput;
    Prompt::setOutput($buffered);

    $collector->collectInstitutionInput($command);

    Prompt::setOutput(new BufferedOutput);

    $output = $buffered->fetch();

    expect($output)->toContain('institution');
});

test('collectResourceGroupInput outputs info message', function (): void {
    $collector = new InstitutionInputCollector;
    $institution = Institution::factory()->create();

    usePromptFallback(
        textResponses: ['n', 'n', 's', 'si', 'si', 'pl', 'pl', 'd', 'd'],
        confirmResponses: [false],
    );

    $buffered = new BufferedOutput;
    Prompt::setOutput($buffered);

    $collector->collectResourceGroupInput($institution);

    Prompt::setOutput(new BufferedOutput);

    $output = $buffered->fetch();

    expect($output)->toContain('resource group');
});

test('collectInstitutionInput short_title prompt is required', function (): void {
    $collector = new InstitutionInputCollector;

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('title')->andReturn('T');
    $command->shouldReceive('option')->with('short-title')->andReturn(null);
    $command->shouldReceive('option')->with('slug')->andReturn('t');
    $command->shouldReceive('option')->with('location')->andReturn('l');
    $command->shouldReceive('option')->with('home-uri')->andReturn('h');
    $command->shouldReceive('option')->with('email')->andReturn('e@e.e');
    $command->shouldReceive('option')->with('logo-uri')->andReturn('lo');
    $command->shouldReceive('option')->with('teaser-uri')->andReturn('te');
    $command->shouldReceive('option')->with('active')->andReturn('yes');

    $shortTitleRequired = false;

    TextPrompt::fallbackUsing(function (TextPrompt $prompt) use (&$shortTitleRequired): string {
        if (str_contains($prompt->label, 'Short title')) {
            $shortTitleRequired = $prompt->required === true;
        }

        return 'value';
    });

    $reflection = new ReflectionClass(Prompt::class);
    $property = $reflection->getProperty('shouldFallback');
    $property->setValue(true);

    MultiSelectPrompt::fallbackUsing(fn (MultiSelectPrompt $prompt): array => []);
    ConfirmPrompt::fallbackUsing(fn (ConfirmPrompt $prompt): bool => true);

    $collector->collectInstitutionInput($command);

    expect($shortTitleRequired)->toBeTrue();
});

test('collectInstitutionInput slug prompt is required', function (): void {
    $collector = new InstitutionInputCollector;

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('title')->andReturn('T');
    $command->shouldReceive('option')->with('short-title')->andReturn(null);
    $command->shouldReceive('option')->with('slug')->andReturn(null);
    $command->shouldReceive('option')->with('location')->andReturn('l');
    $command->shouldReceive('option')->with('home-uri')->andReturn('h');
    $command->shouldReceive('option')->with('email')->andReturn('e@e.e');
    $command->shouldReceive('option')->with('logo-uri')->andReturn('lo');
    $command->shouldReceive('option')->with('teaser-uri')->andReturn('te');
    $command->shouldReceive('option')->with('active')->andReturn('yes');

    $slugRequired = false;

    TextPrompt::fallbackUsing(function (TextPrompt $prompt) use (&$slugRequired): string {
        if (str_contains($prompt->label, 'Slug')) {
            $slugRequired = $prompt->required === true;
        }

        return 'value';
    });

    $reflection = new ReflectionClass(Prompt::class);
    $property = $reflection->getProperty('shouldFallback');
    $property->setValue(true);

    MultiSelectPrompt::fallbackUsing(fn (MultiSelectPrompt $prompt): array => []);
    ConfirmPrompt::fallbackUsing(fn (ConfirmPrompt $prompt): bool => true);

    $collector->collectInstitutionInput($command);

    expect($slugRequired)->toBeTrue();
});

test('collectInstitutionInput multiselect options contain all 7 day keys (lines 39-45 RemoveArrayItem)', function (): void {
    $collector = new InstitutionInputCollector;

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('title')->andReturn('T');
    $command->shouldReceive('option')->with('short-title')->andReturn('T');
    $command->shouldReceive('option')->with('slug')->andReturn('t');
    $command->shouldReceive('option')->with('location')->andReturn('l');
    $command->shouldReceive('option')->with('home-uri')->andReturn('h');
    $command->shouldReceive('option')->with('email')->andReturn('e@e.e');
    $command->shouldReceive('option')->with('logo-uri')->andReturn('lo');
    $command->shouldReceive('option')->with('teaser-uri')->andReturn('te');
    $command->shouldReceive('option')->with('active')->andReturn('yes');

    /** @var array<string, string> $capturedOptions */
    $capturedOptions = [];

    TextPrompt::fallbackUsing(fn (TextPrompt $prompt): string => 'value');

    MultiSelectPrompt::fallbackUsing(function (MultiSelectPrompt $prompt) use (&$capturedOptions): array {
        /** @var array<string, string> $opts */
        $opts = $prompt->options;
        $capturedOptions = $opts;

        return [];
    });

    ConfirmPrompt::fallbackUsing(fn (ConfirmPrompt $prompt): bool => true);

    $reflection = new ReflectionClass(Prompt::class);
    $property = $reflection->getProperty('shouldFallback');
    $property->setValue(true);

    $collector->collectInstitutionInput($command);

    expect($capturedOptions)->toHaveKey('1')
        ->and($capturedOptions)->toHaveKey('2')
        ->and($capturedOptions)->toHaveKey('3')
        ->and($capturedOptions)->toHaveKey('4')
        ->and($capturedOptions)->toHaveKey('5')
        ->and($capturedOptions)->toHaveKey('6')
        ->and($capturedOptions)->toHaveKey('7')
        ->and(count($capturedOptions))->toBe(7);
});

test('collectInstitutionInput multiselect scroll is exactly 7', function (): void {
    $collector = new InstitutionInputCollector;

    $command = Mockery::mock(Command::class);
    $command->shouldReceive('option')->with('title')->andReturn('T');
    $command->shouldReceive('option')->with('short-title')->andReturn('T');
    $command->shouldReceive('option')->with('slug')->andReturn('t');
    $command->shouldReceive('option')->with('location')->andReturn('l');
    $command->shouldReceive('option')->with('home-uri')->andReturn('h');
    $command->shouldReceive('option')->with('email')->andReturn('e@e.e');
    $command->shouldReceive('option')->with('logo-uri')->andReturn('lo');
    $command->shouldReceive('option')->with('teaser-uri')->andReturn('te');
    $command->shouldReceive('option')->with('active')->andReturn('yes');

    $capturedScroll = 0;

    TextPrompt::fallbackUsing(fn (TextPrompt $prompt): string => 'value');

    MultiSelectPrompt::fallbackUsing(function (MultiSelectPrompt $prompt) use (&$capturedScroll): array {
        $capturedScroll = $prompt->scroll;

        return [];
    });

    ConfirmPrompt::fallbackUsing(fn (ConfirmPrompt $prompt): bool => true);

    $reflection = new ReflectionClass(Prompt::class);
    $property = $reflection->getProperty('shouldFallback');
    $property->setValue(true);

    $collector->collectInstitutionInput($command);

    expect($capturedScroll)->toBe(7);
});
