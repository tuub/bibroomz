<?php

declare(strict_types=1);

use App\Http\Requests\SwitchLanguageRequest;

covers(SwitchLanguageRequest::class);

test('switch language request validates locale is in supported locales', function (): void {
    $request = new SwitchLanguageRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('locale');

    $localeRules = (array) $rules['locale'];
    expect($localeRules)->toContain('required');

    $hasInRule = false;
    foreach ($localeRules as $rule) {
        if (is_string($rule) && str_starts_with($rule, 'in:')) {
            $hasInRule = true;
            break;
        }
    }
    expect($hasInRule)->toBeTrue();
});

test('switch language request locale method returns validated locale', function (): void {
    $mockRequest = new class('en') extends SwitchLanguageRequest
    {
        public function __construct(private readonly string $testValue)
        {
            parent::__construct();
        }

        public function validated(mixed $key = null, mixed $default = null): mixed
        {
            if ($key === 'locale') {
                return $this->testValue;
            }

            return parent::validated($key, $default);
        }
    };

    expect($mockRequest->locale())->toBe('en');
});

test('switch language request locale method returns default locale when null', function (): void {
    $defaultLocale = app()->getLocale();

    $mockRequest = new class extends SwitchLanguageRequest
    {
        public function validated(mixed $key = null, mixed $default = null): mixed
        {
            if ($key === 'locale') {
                return null;
            }

            return parent::validated($key, $default);
        }
    };

    expect($mockRequest->locale())->toBe($defaultLocale);
});

test('switch language request authorize returns true', function (): void {
    $request = new SwitchLanguageRequest;

    expect($request->authorize())->toBeTrue();
});
