<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

final class ImportUsersTestStreamWrapper
{
    /** @var array<string, string> */
    public static array $contents = [];

    /** @var array<string, int> */
    public static array $closeCounts = [];

    private string $key = '';

    private int $position = 0;

    public static function put(string $key, string $contents): string
    {
        self::$contents[$key] = $contents;
        self::$closeCounts[$key] = 0;

        return 'roomz-import-users-unit://'.$key;
    }

    public static function closeCount(string $key): int
    {
        return self::$closeCounts[$key] ?? 0;
    }

    public static function reset(): void
    {
        self::$contents = [];
        self::$closeCounts = [];
    }

    public function stream_open(string $path, string $mode): bool
    {
        $key = $this->keyFromPath($path);

        if (! str_contains($mode, 'r') || ! array_key_exists($key, self::$contents)) {
            return false;
        }

        $this->key = $key;
        $this->position = 0;

        return true;
    }

    public function stream_read(int $count): string
    {
        $chunk = substr(self::$contents[$this->key], $this->position, $count);
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen(self::$contents[$this->key]);
    }

    /**
     * @return array<int|string, int>
     */
    public function stream_stat(): array
    {
        return $this->buildStat($this->key);
    }

    /**
     * @return array<int|string, int>|false
     */
    public function url_stat(string $path): array|false
    {
        $key = $this->keyFromPath($path);

        if (! array_key_exists($key, self::$contents)) {
            return false;
        }

        return $this->buildStat($key);
    }

    public function stream_close(): void
    {
        self::$closeCounts[$this->key] = (self::$closeCounts[$this->key] ?? 0) + 1;
    }

    private function keyFromPath(string $path): string
    {
        return (string) preg_replace('/^roomz-import-users-unit:\/\//', '', $path);
    }

    /**
     * @return array<int|string, int>
     */
    private function buildStat(string $key): array
    {
        $time = time();
        $size = strlen(self::$contents[$key] ?? '');

        return [
            0 => 0,
            1 => 0,
            2 => 0100644,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => $size,
            8 => $time,
            9 => $time,
            10 => $time,
            11 => -1,
            12 => -1,
            'dev' => 0,
            'ino' => 0,
            'mode' => 0100644,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => $size,
            'atime' => $time,
            'mtime' => $time,
            'ctime' => $time,
            'blksize' => -1,
            'blocks' => -1,
        ];
    }
}
