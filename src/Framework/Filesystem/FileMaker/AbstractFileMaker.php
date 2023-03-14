<?php

namespace Os\Framework\Filesystem\FileMaker;

use Os\Framework\Debug\Dumper;

abstract class AbstractFileMaker
{
    abstract public static function getExtension(): string;
    abstract public function getFileName(): string;
    abstract public function getAbsolutePath(): string;
    abstract protected function generateContent(): string;

    public function make(): bool
    {
        $content = $this->generateContent();
        $path = sprintf("%s/%s.%s", $this->getAbsolutePath(), $this->getFileName(), static::getExtension());
        $fp = fopen($path, "w+");
        fwrite($fp, $content);
        fclose($fp);
        return true;
    }
}