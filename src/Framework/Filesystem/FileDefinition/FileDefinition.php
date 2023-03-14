<?php

namespace Os\Framework\Filesystem\FileDefinition;

class FileDefinition
{
    public function __construct(protected string $fileName, protected array $pathParts){}

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return array
     */
    public function getPathParts(): array
    {
        return $this->pathParts;
    }

    public function getPath(): string {
        return implode(DIRECTORY_SEPARATOR, $this->pathParts);
    }
}