#!/usr/bin/env php
<?php

declare(strict_types=1);

use Scripts\BrowserTestRunner;

require_once __DIR__.'/BrowserTestRunner.php';

exit((new BrowserTestRunner)->run($_SERVER['argv'] ?? null));
