<?php

namespace App\Services\Closings;

use App\Models\Closing;
use Illuminate\Support\Arr;

class UpdateClosingAction
{
    public function __construct(
        private ClosingDataSanitizer $dataSanitizer,
        private ClosingEventDispatcher $closingEventDispatcher,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(Closing $closing, array $attributes): Closing
    {
        $closingData = $this->normalizeStringKeys(Arr::except(
            $this->dataSanitizer->sanitize($attributes),
            ['closable_id', 'closable_type'],
        ));

        if ($closing->update($closingData)) {
            $this->closingEventDispatcher->dispatchUpdated($closing);
        }

        return $closing;
    }

    /**
     * @param array<mixed> $values
     * @return array<string, mixed>
     */
    private function normalizeStringKeys(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
