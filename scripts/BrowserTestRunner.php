<?php

declare(strict_types=1);

namespace Scripts;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class BrowserTestRunner
{
    private const string BLUE = "\033[1;34m";

    private const string GREEN = "\033[1;32m";

    private const string RED = "\033[1;31m";

    private const string RESET = "\033[0m";

    private const string BROWSER_APP_KEY = 'base64:TF9u2T3Sw37w0oo3Ax8hn7XJWrD8mBcndOwWw7AkGXQ=';

    private const string BROWSER_HOST = '127.0.0.1';

    private readonly string $rootDir;

    private readonly string $dbFile;

    private readonly string $reverbLog;

    private readonly string $serverLog;

    private readonly string $nullDevice;

    private readonly string $phpBinary;

    private ?string $browserStorageDir = null;

    /** @var list<array{process: resource, pid:int, log:string}> */
    private array $backgroundProcesses = [];

    public function __construct(?string $rootDir = null)
    {
        $resolvedRoot = $rootDir;

        if ($resolvedRoot === null || $resolvedRoot === '') {
            $resolvedRoot = getenv('ROOT_DIR');
        }

        if (! is_string($resolvedRoot) || $resolvedRoot === '') {
            $resolvedRoot = getcwd();
        }

        if (! is_string($resolvedRoot)) {
            $this->fail('Could not determine project root.');
        }

        $this->rootDir = realpath($resolvedRoot) ?: $resolvedRoot;
        $this->dbFile = $this->rootDir.'/database/browser-testing.sqlite';
        $this->reverbLog = sys_get_temp_dir().'/roomz-reverb.log';
        $this->serverLog = sys_get_temp_dir().'/roomz-serve.log';
        $this->nullDevice = DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';
        $this->phpBinary = PHP_BINARY;
    }

    public function run(mixed $argv): int
    {
        register_shutdown_function([$this, 'cleanup']);
        $this->registerSignalHandlers();

        $playwrightBinary = $this->rootDir.'/node_modules/.bin/playwright';
        $browserArguments = $this->browserArguments($argv);

        $this->step('Checking prerequisites');

        if (! is_file($playwrightBinary) || ! is_executable($playwrightBinary)) {
            $this->fail('Playwright is not installed. Run npm ci first.');
        }

        $playwrightVersion = $this->captureCommand([$playwrightBinary, '--version']);

        if ($playwrightVersion['exitCode'] !== 0) {
            $playwrightError = trim($playwrightVersion['stderr']);
            $this->fail($playwrightError !== '' ? $playwrightError : 'Could not determine Playwright version.');
        }

        $this->ok('playwright: '.trim($playwrightVersion['stdout']));

        $playwrightBrowsersPath = getenv('PLAYWRIGHT_BROWSERS_PATH');

        if (is_string($playwrightBrowsersPath) && $playwrightBrowsersPath !== '' && ! is_dir($playwrightBrowsersPath)) {
            $this->fail(sprintf('PLAYWRIGHT_BROWSERS_PATH does not exist: %s', $playwrightBrowsersPath));
        }

        $this->step('Selecting ports');
        $serverPort = $this->findUnusedPort(self::BROWSER_HOST);
        $reverbPort = $this->findUnusedPort(self::BROWSER_HOST, [$serverPort]);
        $this->ok(sprintf('serve port: %d', $serverPort));
        $this->ok(sprintf('reverb port: %d', $reverbPort));

        foreach ($this->runtimeEnvironment($serverPort, $reverbPort) as $key => $value) {
            $this->setEnvironmentVariable($key, $value);
        }

        $this->step('Preparing database');
        $this->ensureDirectory(dirname($this->dbFile));

        if (! is_file($this->dbFile) && file_put_contents($this->dbFile, '') === false) {
            $this->fail(sprintf('Could not create database file: %s', $this->dbFile));
        }

        $this->step('Preparing runtime storage');
        $this->browserStorageDir = $this->createTemporaryDirectory('roomz-browser-storage.');
        $this->setEnvironmentVariable('LARAVEL_STORAGE_PATH', $this->browserStorageDir);
        $this->ensureDirectory($this->browserStorageDir.'/app/public');
        $this->ensureDirectory($this->browserStorageDir.'/framework/cache/data');
        $this->ensureDirectory($this->browserStorageDir.'/framework/sessions');
        $this->ensureDirectory($this->browserStorageDir.'/framework/testing');
        $this->ensureDirectory($this->browserStorageDir.'/framework/views');
        $this->ensureDirectory($this->browserStorageDir.'/logs');
        $this->ok(sprintf('storage: %s', $this->browserStorageDir));

        $this->step('Building frontend assets');
        $this->runCommandOrFail([$this->phpBinary, 'artisan', 'ziggy:generate']);
        $this->runCommandOrFail(['npm', 'run', 'build']);
        $this->ok('frontend assets');

        $this->step('Preparing database schema');
        $this->runCommandOrFail([$this->phpBinary, 'artisan', 'migrate:fresh', '--force']);
        $this->ok(sprintf('database: %s', $this->dbFile));

        $this->step('Starting services');
        $this->backgroundProcesses[] = $this->startBackgroundProcess(
            [
                $this->phpBinary,
                'artisan',
                'reverb:start',
                '--host='.self::BROWSER_HOST,
                '--port='.$reverbPort,
            ],
            $this->reverbLog,
        );
        printf(
            "    reverb pid %d (log: %s)\n",
            $this->backgroundProcesses[array_key_last($this->backgroundProcesses)]['pid'],
            $this->reverbLog,
        );

        $this->backgroundProcesses[] = $this->startBackgroundProcess(
            [$this->phpBinary, 'artisan', 'serve', '--host='.self::BROWSER_HOST, '--port='.$serverPort],
            $this->serverLog,
        );
        printf(
            "    serve  pid %d (log: %s)\n",
            $this->backgroundProcesses[array_key_last($this->backgroundProcesses)]['pid'],
            $this->serverLog,
        );

        printf('    waiting for reverb:%d ...', $reverbPort);

        if (! $this->waitForTcp(self::BROWSER_HOST, $reverbPort)) {
            echo PHP_EOL;
            $this->dumpLog($this->reverbLog);

            return 1;
        }

        echo " ready\n";
        printf('    waiting for serve:%d  ...', $serverPort);

        $appUrl = sprintf('http://%s:%d', self::BROWSER_HOST, $serverPort);

        if (! $this->waitForHttp($appUrl)) {
            echo PHP_EOL;
            $this->dumpLog($this->serverLog);

            return 1;
        }

        echo " ready\n";
        $this->step('Running browser tests');

        return $this->runCommandOrFail($this->buildPestCommand($browserArguments));
    }

    public function cleanup(): void
    {
        foreach (array_reverse($this->backgroundProcesses) as $processData) {
            $this->stopBackgroundProcess($processData);
        }

        if ($this->browserStorageDir !== null && is_dir($this->browserStorageDir)) {
            $this->removeDirectory($this->browserStorageDir);
        }
    }

    private function registerSignalHandlers(): void
    {
        if (! function_exists('pcntl_async_signals') || ! function_exists('pcntl_signal')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGINT, static function (): never {
            exit(130);
        });

        pcntl_signal(SIGTERM, static function (): never {
            exit(143);
        });
    }

    private function step(string $message): void
    {
        printf(self::BLUE.'==> %s'.self::RESET.PHP_EOL, $message);
    }

    private function ok(string $message): void
    {
        printf(self::GREEN.'    ✓ %s'.self::RESET.PHP_EOL, $message);
    }

    private function fail(string $message, int $exitCode = 1): never
    {
        fwrite(STDERR, self::RED.$message.self::RESET.PHP_EOL);
        exit($exitCode);
    }

    private function setEnvironmentVariable(string $key, string $value): void
    {
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    /**
     * @return array<string, string>
     */
    private function runtimeEnvironment(int $serverPort, int $reverbPort): array
    {
        return [
            'APP_ENV' => 'testing',
            'APP_DEBUG' => 'false',
            'APP_KEY' => self::BROWSER_APP_KEY,
            'APP_TIMEZONE' => 'Europe/Berlin',
            'APP_URL' => sprintf('http://%s:%d', self::BROWSER_HOST, $serverPort),
            'CACHE_DRIVER' => 'file',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $this->dbFile,
            'REVERB_SCHEME' => 'http',
            'REVERB_HOST' => self::BROWSER_HOST,
            'REVERB_PORT' => (string) $reverbPort,
            'SESSION_DRIVER' => 'file',
            'TELESCOPE_ENABLED' => 'false',
            'TZ' => 'Europe/Berlin',
            'VITE_REVERB_PORT' => (string) $reverbPort,
        ];
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            $this->fail(sprintf('Could not create directory: %s', $directory));
        }
    }

    private function createTemporaryDirectory(string $prefix): string
    {
        $baseDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $candidate = $baseDirectory.DIRECTORY_SEPARATOR.$prefix.bin2hex(random_bytes(6));

            if (@mkdir($candidate, 0700)) {
                return $candidate;
            }
        }

        $this->fail('Could not create temporary browser storage directory.');
    }

    /**
     * @param  list<string>  $command
     * @return array{exitCode:int, stdout:string, stderr:string}
     */
    private function captureCommand(array $command): array
    {
        $process = proc_open(
            implode(' ', array_map(escapeshellarg(...), $command)),
            [
                0 => ['file', 'php://stdin', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $this->rootDir,
        );

        if (! is_resource($process)) {
            $this->fail(sprintf('Could not start command: %s', implode(' ', $command)));
        }

        $stdout = stream_get_contents($pipes[1]) ?: '';
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }

    /**
     * @param  list<string>  $command
     */
    private function runCommandOrFail(array $command): int
    {
        $process = proc_open(
            implode(' ', array_map(escapeshellarg(...), $command)),
            [
                0 => ['file', 'php://stdin', 'r'],
                1 => ['file', 'php://stdout', 'w'],
                2 => ['file', 'php://stderr', 'w'],
            ],
            $pipes,
            $this->rootDir,
        );

        if (! is_resource($process)) {
            $this->fail(sprintf('Could not start command: %s', implode(' ', $command)));
        }

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            exit($exitCode);
        }

        return $exitCode;
    }

    /**
     * @param  list<string>  $command
     * @return array{process: resource, pid:int, log:string}
     */
    private function startBackgroundProcess(array $command, string $logPath): array
    {
        $process = proc_open(
            implode(' ', array_map(escapeshellarg(...), $command)),
            [
                0 => ['file', $this->nullDevice, 'r'],
                1 => ['file', $logPath, 'a'],
                2 => ['file', $logPath, 'a'],
            ],
            $pipes,
            $this->rootDir,
        );

        if (! is_resource($process)) {
            $this->fail(sprintf('Could not start background command: %s', implode(' ', $command)));
        }

        $status = proc_get_status($process);

        return [
            'process' => $process,
            'pid' => $status['pid'],
            'log' => $logPath,
        ];
    }

    /**
     * @param  array{process: resource, pid:int, log:string}  $processData
     */
    private function stopBackgroundProcess(array $processData): void
    {
        $process = $processData['process'];

        if (! is_resource($process)) {
            return;
        }

        $status = proc_get_status($process);

        if ($status['running']) {
            proc_terminate($process);

            for ($attempt = 0; $attempt < 10; $attempt++) {
                usleep(100000);
                $status = proc_get_status($process);

                if (! $status['running']) {
                    break;
                }
            }

            if ($status['running']) {
                proc_terminate($process, 9);
            }
        }

        proc_close($process);
    }

    /**
     * @param  list<int>  $excludedPorts
     */
    private function findUnusedPort(string $host, array $excludedPorts = []): int
    {
        for ($attempt = 0; $attempt < 200; $attempt++) {
            $port = random_int(40000, 65000);

            if (in_array($port, $excludedPorts, true)) {
                continue;
            }

            $server = @stream_socket_server(sprintf('tcp://%s:%d', $host, $port), $errorNumber, $errorMessage);

            if (is_resource($server)) {
                fclose($server);

                return $port;
            }
        }

        $this->fail('Could not find a free high TCP port.');
    }

    private function waitForTcp(string $host, int $port, int $attempts = 60): bool
    {
        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $socket = @fsockopen($host, $port, $errorNumber, $errorMessage, 1.0);

            if (is_resource($socket)) {
                fclose($socket);

                return true;
            }

            sleep(1);
        }

        fwrite(
            STDERR,
            self::RED.sprintf('TCP endpoint did not become ready: %s:%d', $host, $port).self::RESET.PHP_EOL,
        );

        return false;
    }

    private function waitForHttp(string $url, int $attempts = 60): bool
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            $this->fail(sprintf('Invalid URL for readiness check: %s', $url));
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? 80;
        $path = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $socket = @fsockopen($host, $port, $errorNumber, $errorMessage, 1.0);

            if (is_resource($socket)) {
                stream_set_timeout($socket, 2);
                fwrite($socket, "GET {$path} HTTP/1.0\r\nHost: {$host}\r\nConnection: close\r\n\r\n");
                $statusLine = fgets($socket);
                fclose($socket);

                if (is_string($statusLine) && preg_match('/^HTTP\/\d\.\d\s+(\d{3})\b/', $statusLine, $matches) === 1) {
                    $statusCode = (int) $matches[1];

                    if ($statusCode >= 200 && $statusCode < 400) {
                        return true;
                    }
                }
            }

            sleep(1);
        }

        fwrite(STDERR, self::RED.sprintf('HTTP server did not become ready: %s', $url).self::RESET.PHP_EOL);

        return false;
    }

    private function dumpLog(string $logPath): void
    {
        if (! is_file($logPath)) {
            return;
        }

        $contents = file_get_contents($logPath);

        if ($contents === false || $contents === '') {
            return;
        }

        fwrite(STDERR, $contents);
    }

    /**
     * @param  list<string>  $browserArguments
     * @return list<string>
     */
    private function buildPestCommand(array $browserArguments): array
    {
        return array_merge([
            $this->rootDir.'/vendor/bin/pest',
            '--cache-directory=/tmp/phpunit-cache',
            '--display-all-issues',
            '--parallel',
            '--testsuite=Browser',
            '--configuration',
            'phpunit.browser.xml',
            '--coverage',
            '--coverage-cobertura',
            'build/coverage/cobertura-browser.xml',
        ], $browserArguments);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $entry) {
            if (! $entry instanceof SplFileInfo) {
                continue;
            }

            if ($entry->isDir()) {
                @rmdir($entry->getPathname());

                continue;
            }

            @unlink($entry->getPathname());
        }

        @rmdir($directory);
    }

    /**
     * @return list<string>
     */
    private function browserArguments(mixed $argv): array
    {
        if (! is_array($argv)) {
            return [];
        }

        $arguments = [];

        foreach (array_slice($argv, 1) as $argument) {
            if (! is_scalar($argument)) {
                continue;
            }

            $arguments[] = (string) $argument;
        }

        return $arguments;
    }
}
