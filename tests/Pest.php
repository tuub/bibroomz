<?php

declare(strict_types=1);

use Tests\BrowserTestCase;
use Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
uses(BrowserTestCase::class)->in('Browser');

if (getenv('CI')) {
    pest()->browser()->timeout(60_000);
}

require_once __DIR__.'/Helpers.php';
require_once __DIR__.'/Browser/Support.php';
require_once __DIR__.'/NamespaceOverrides.php';
