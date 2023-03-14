<?php

namespace Os\Framework\Filesystem\Exception;

use Throwable;

class FileReadingException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(string $fileName, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("Error reading file '%s' (make sure the file does exist and is accessible)", $fileName), previous: $previous);
    }
}