#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/BrowserTestRunner.php';

exit((new Scripts\BrowserTestRunner())->run($_SERVER['argv'] ?? null));
