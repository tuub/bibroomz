<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Rector\Class_\ModelCastsPropertyToCastsMethodRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/scripts',
        __DIR__.'/tests',
        __DIR__.'/tools',

        __DIR__.'/rector.php',
    ])
    // Rector caches in the system temp directory by default, where CI cannot
    // reach it between pipelines. A cold run is a minute, a cached one seconds.
    ->withCache(cacheDirectory: __DIR__.'/.rector-cache')
    ->withPhpSets()
    // Reads the installed laravel/framework version out of composer.lock and
    // registers the upgrade rules up to it, so this keeps pace with the
    // framework on its own rather than naming a version that has to be bumped.
    ->withComposerBased(laravel: true)
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
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
