<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Rector\Class_\ModelCastsPropertyToCastsMethodRector;
use RectorLaravel\Set\LaravelLevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/scripts',
        __DIR__.'/tests',

        __DIR__.'/rector.php',
    ])
    ->withPhpSets()
    ->withSets([
        LaravelLevelSetList::UP_TO_LARAVEL_130,

        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
    ])
    ->withSkip([
        __DIR__.'/bootstrap/cache',

        ModelCastsPropertyToCastsMethodRector::class,
        // Removing trailing null args is unsafe when null is a meaningful value
        // e.g. Collection::where('key', '!==', null) → where('key', '!==') changes semantics
        RemoveExtraParametersRector::class,
        RemoveNullArgOnNullDefaultParamRector::class,

        // A migration matches class names that were written into the database
        // when it ran, not the ones the code uses today. ::class follows a
        // rename of the model and the stored rows do not, so the two would
        // silently stop matching.
        StringClassNameToClassConstantRector::class => [
            __DIR__.'/database/migrations',
        ],
    ]);
