<?php

namespace Os\Framework\Filesystem\Exception;

use Throwable;

class CannotCreateDirectoryException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(protected string $path, protected ?string $reason = null, ?Throwable $previous = null)
    {
        $message = sprintf("Cannot create directory '%s'", $this->path);
        if($this->reason !== null)
            $message = sprintf("%s, reason: '%s'", $message, $this->reason);
        parent::__construct(message: $message, previous: $previous);
    }
}