<?php

/**
 * Namespace-scoped overrides for built-in functions.
 *
 * PHP resolves unqualified function calls by checking the current namespace first,
 * then falling back to the global namespace. Declaring these here lets tests
 * simulate error conditions that cannot be provoked with real inputs.
 */

namespace App\Auth {
    /**
     * Intercepts preg_replace() calls from AlmaUserProvider so tests can simulate
     * a PCRE catastrophic error (the only condition under which preg_replace returns null).
     *
     * AlmaUserProvider only calls preg_replace() with string arguments, so this
     * override uses a string-only signature to satisfy PHPStan at max level.
     */
    function preg_replace(string $pattern, string $replacement, string $subject, int $limit = -1, ?int &$count = null): ?string
    {
        if ($GLOBALS['__test_preg_replace_returns_null'] ?? false) {
            return null;
        }

        /** @var string $result */
        $result = \preg_replace($pattern, $replacement, $subject, $limit, $count);

        return $result;
    }
}

namespace App\Console\Commands {
    /**
     * Intercepts fopen() calls from ImportUsers so tests can simulate the rare case where
     * a file passes is_file()/is_readable() but fopen() still fails (e.g. permission race).
     */
    function fopen(string $filename, string $mode): mixed
    {
        if ($GLOBALS['__test_fopen_returns_false'] ?? false) {
            return false;
        }

        return \fopen($filename, $mode);
    }
}
