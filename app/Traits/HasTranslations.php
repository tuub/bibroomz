<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;
use Spatie\Translatable\HasTranslations as BaseHasTranslations;

trait HasTranslations
{
    use BaseHasTranslations;

    /**
     * @return array<string, mixed>
     */
    public function withoutTranslations(): array
    {
        $attributes = $this->normalizeAttributes(parent::toArray());

        foreach ($this->getTranslatableAttributes() as $field) {
            /** @var string $field */
            $attributes[$field] = $this->getTranslation($field, App::getLocale());
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function withTranslations(): array
    {
        return $this->normalizeAttributes(parent::toArray());
    }

    /**
     * @param  array<mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
