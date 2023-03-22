<?php

namespace Os\Framework\Filesystem;

use Os\Framework\Filesystem\Exception\FileNotFoundException;
use Os\Framework\Filesystem\Exception\FileReadingException;
use Os\Framework\Filesystem\FileDefinition\FileDefinition;

class Filesystem
{
    protected array $cache;

    public const FLAG_NONE = "none";
    public const FLAG_ONLY_FILES = "only_files";
    public const FLAG_ONLY_DIRS = "only_dirs";

    public function __construct()
    {
        $this->cache = [];
    }

    public function clearCache(){
        unset($this->cache);
        $this->cache = [];
    }

    public function __destruct(){
        $this->clearCache();
    }

    /**
     * @throws FileNotFoundException
     * @throws FileReadingException
     */
    public function read(string $fileName, int $offset = 0, ?int $length = null, bool $reloadCache = false): string
    {
        if(!file_exists($fileName)) throw new FileNotFoundException($fileName);
        $real = realpath($fileName);
        if(!isset($this->cache[$real]) || $reloadCache === true){
            $content = file_get_contents(filename: $real, offset: $offset, length: $length);
            if($content === false)
                throw new FileReadingException($real);
            $this->cache[$real] = $content;
        }
        return $this->cache[$real];
    }

    public function write(string $fileName, string $content, string $mode = "w+")
    {
        $fileHandle = fopen($fileName, $mode);
        fwrite($fileHandle, $content);
        /*foreach(preg_split("/((\r?\n)|(\r\n?))/", $content) as $line){
            fwrite($fileHandle, sprintf("%s\n\r", $line));
        }*/
        fclose($fileHandle);
    }

    public function delete(string $fileName): bool
    {
        return unlink($fileName);
    }

    public function touch(string $fileName): bool
    {
        $fileDefinition = $this->parseFileName($fileName);
        if(!$this->directoryExists($fileDefinition->getPath()))
            $this->createDirectory($fileDefinition->getPath());
        return touch($fileName);
    }

    public function fileExists(string $fileName): bool
    {
        return is_file(realpath($fileName)) && file_exists(realpath($fileName));
    }

    public function directoryExists(string $directory): bool
    {
        return is_dir(realpath($directory));
    }

    public function createDirectory(string $directory): bool
    {
        return mkdir(directory: $directory, recursive: true);
    }

    protected function parseFileName(string $fileName): FileDefinition
    {
        $parts = explode(DIRECTORY_SEPARATOR, $fileName);
        $_fileName = $parts[array_key_last($parts)];
        array_pop($parts);
        return new FileDefinition($_fileName, $parts);
    }

    public function ls(string $directory, string $flag = self::FLAG_NONE): ?array
    {
        if(!$this->directoryExists($directory)) return null;
        $items = scandir($directory);
        $_items = [];
        switch($flag){
            case self::FLAG_ONLY_DIRS:
                foreach($items as $item) {
                    if($item === "." || $item === "..") continue;
                    if($this->directoryExists(self::buildPath($directory, $item))){
                        $_items[] = $item;
                    }
                }
                break;
            case self::FLAG_ONLY_FILES:
                foreach($items as $item) {
                    if($item === "." || $item === "..") continue;
                    if($this->fileExists(self::buildPath($directory, $item))){
                        $_items[] = $item;
                    }
                }
                break;
            default:
                foreach($items as $item) {
                    if($item === "." || $item === "..") continue;
                    $_items[] = $item;
                }
                break;
        }

        return $_items;
    }

    public static function buildPath(string ...$parts): string
    {
        $pre = "";
        if(str_starts_with($parts[array_key_first($parts)], DIRECTORY_SEPARATOR))
            $pre = DIRECTORY_SEPARATOR;
        foreach($parts as $i => $part){
            $parts[$i] = trim($part, DIRECTORY_SEPARATOR);
        }
        return sprintf("%s%s", $pre, implode(DIRECTORY_SEPARATOR, $parts));
    }
}