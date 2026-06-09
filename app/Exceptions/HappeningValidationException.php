<?php

namespace App\Exceptions;

use RuntimeException;

class HappeningValidationException extends RuntimeException
{
    /**
     * @param  array<string, bool|float|int|string|null>  $context
     */
    public function __construct(
        public string $translationKey,
        public array $context = [],
    ) {
        parent::__construct(__($translationKey, $context), 400);
    }

    public function translatedMessage(): string
    {
        return __($this->translationKey, $this->context);
    }
}
