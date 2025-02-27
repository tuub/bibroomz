<?php

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use RectorLaravel\Rector\Class_\ModelCastsPropertyToCastsMethodRector;
use RectorLaravel\Set\LaravelLevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withPhpSets()
    ->withSets([
        LaravelLevelSetList::UP_TO_LARAVEL_110,
    ])
    ->withSkip([
        ClosureToArrowFunctionRector::class,
        ModelCastsPropertyToCastsMethodRector::class,
        NullToStrictStringFuncCallArgRector::class,
        ReadOnlyPropertyRector::class,
    ]);
