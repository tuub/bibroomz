<?php

namespace App\Traits;

use App;
use Spatie\Translatable\HasTranslations as BaseHasTranslations;

trait HasTranslations
{
    use BaseHasTranslations;

    public function withoutTranslations()
    {
        $attributes = parent::toArray();

        foreach ($this->getTranslatableAttributes() as $field) {
            $attributes[$field] = $this->getTranslation($field, App::getLocale());
        }

        return $attributes;
    }

    public function withTranslations()
    {
        return parent::toArray();
    }
}
