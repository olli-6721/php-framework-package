<?php

namespace Os\Framework\Filesystem\Exception;

use Throwable;

class NoFileExtensionFoundException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(string $fileName, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("No file-extension found in filename '%s'", $fileName), previous: $previous);
    }
}