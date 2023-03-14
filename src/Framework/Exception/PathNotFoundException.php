<?php

namespace Os\Framework\Exception;

use Throwable;

class PathNotFoundException extends FrameworkException
{
    public function __construct(string $path, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("Path '%s' not found", $path), previous: $previous);
    }
}