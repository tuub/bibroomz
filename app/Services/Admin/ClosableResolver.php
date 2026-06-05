<?php

namespace App\Services\Admin;

use App\Models\Closing;
use App\Models\Institution;
use App\Models\Resource;

class ClosableResolver
{
    public function resolve(string $closableType, string $closableId): Institution|Resource
    {
        return Closing::getClosableModel($closableType)->findOrFail($closableId);
    }

    public function typeForModel(Institution|Resource $closable): string
    {
        $class = class_basename($closable);
        $snakeCase = preg_replace('/(?<!^)[A-Z]/', '_$0', $class);

        return strtolower(is_string($snakeCase) ? $snakeCase : $class);
    }
}
