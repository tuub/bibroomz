<?php

declare(strict_types=1);

namespace App\Services\Admin;

class StatisticsFormatter
{
    public function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    public function toStringValue(mixed $value): string
    {
        return is_string($value) || is_numeric($value) ? (string) $value : '';
    }

    public function percentage(int $part, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($part / $total) * 100, 1);
    }

    public function deltaPercentage(int $current, int $comparison): float
    {
        if ($comparison === 0) {
            return $current === 0 ? 0.0 : 100.0;
        }

        return round((($current - $comparison) / $comparison) * 100, 1);
    }

    /**
     * @return array<string, string>
     */
    public function stringTranslations(mixed $translations): array
    {
        if (! is_array($translations)) {
            return [];
        }

        $result = [];

        foreach ($translations as $locale => $value) {
            if (is_string($locale) && (is_string($value) || is_numeric($value))) {
                $result[$locale] = $this->toStringValue($value);
            }
        }

        return $result;
    }
}
