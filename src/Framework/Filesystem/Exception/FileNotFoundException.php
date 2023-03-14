<?php

namespace Os\Framework\Filesystem\Exception;

use Throwable;

class FileNotFoundException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(protected string $fileName, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("File '%s' not found", $this->fileName), previous: $previous);
    }
}