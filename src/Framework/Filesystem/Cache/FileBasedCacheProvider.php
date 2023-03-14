<?php

namespace Os\Framework\Filesystem\Cache;

use Os\Framework\Filesystem\Filesystem;

class FileBasedCacheProvider implements CacheProviderInterface
{

    protected const CACHE_DIRECTORY = BASE_PATH.DIRECTORY_SEPARATOR."var".DIRECTORY_SEPARATOR."cache";

    /**
     * @param string $key
     * @return mixed|null
     */
    public static function get(string $key): mixed
    {
        $fs = new Filesystem();
        if(!self::exists($key, $fs)) return null;
        try {$value = unserialize(constant(self::getCacheLocalName($key)));}catch (\Throwable $e){$value = null;}
        return empty($value) ? self::loadCacheFile($key, $fs) : $value;
    }

    public static function set(string $key, mixed $data): void
    {
        $fileName = self::getCacheFileName($key);
        $serialized = serialize($data);

        $fs = new Filesystem();
        $fs->write($fileName, $serialized, "w");
        if(!defined(self::getCacheLocalName($key)))
            define(self::getCacheLocalName($key), $serialized);
    }

    public static function delete(string $key, Filesystem $fs = null): void
    {
        if(self::existsInFileCache($key)){
            ($fs ?? (new Filesystem()))->delete(self::getCacheFileName($key));
        }
    }

    /**
     * @description Only deletes files in the cache directory, not the already loaded cache
     */
    public static function clear(): void
    {
        $fs = new Filesystem();
        $cacheFiles = $fs->ls(self::CACHE_DIRECTORY, Filesystem::FLAG_ONLY_FILES);
        foreach ($cacheFiles as $file){
            $fs->delete(sprintf("%s%s%s", self::CACHE_DIRECTORY, DIRECTORY_SEPARATOR, $file));
        }
    }

    public static function exists(string $key, Filesystem $fs = null): bool
    {
        return self::existsInLocalCache($key) || self::existsInFileCache($key, $fs);
    }

    protected static function existsInLocalCache(string $key): bool
    {
        return defined(self::getCacheLocalName($key));
    }

    protected static function existsInFileCache(string $key, Filesystem $fs = null): bool
    {
        return ($fs ?? (new Filesystem()))->fileExists(self::getCacheFileName($key));
    }

    protected static function loadCacheFile(string $key, Filesystem $fs){
        $cacheFileName = self::getCacheFileName($key);
        try {
            $data = $fs->read($cacheFileName);
            return unserialize($data);
        }
        catch (\Throwable $e){
            try {
                $fs->delete($cacheFileName);
            }
            catch (\Throwable $e){}
            return null;
        }
    }

    protected static function getCacheFileName(string $key): string
    {
        return sprintf("%s%s%s.cache", self::CACHE_DIRECTORY, DIRECTORY_SEPARATOR, $key);
    }

    protected static function getCacheLocalName(string $key): string
    {
        return sprintf("CACHE_%s", $key);
    }
}