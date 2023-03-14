<?php

namespace Os\Framework\Filesystem\Exception;

use Throwable;

class CannotCreateFileException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(protected string $fileName, protected ?string $reason = null, ?Throwable $previous = null)
    {
        $message = sprintf("Cannot create file '%s'", $this->fileName);
        if($this->reason !== null)
            $message = sprintf("%s, reason: '%s'", $message, $this->reason);
        parent::__construct(message: $message, previous: $previous);
    }
}