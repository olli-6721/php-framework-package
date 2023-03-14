<?php

namespace Os\Framework\Filesystem\Cache;

interface CacheProviderInterface
{
    public static function get(string $key): mixed;

    public static function set(string $key, mixed $data): void;

    public static function delete(string $key): void;

    public static function clear(): void;

    public static function exists(string $key): bool;
}