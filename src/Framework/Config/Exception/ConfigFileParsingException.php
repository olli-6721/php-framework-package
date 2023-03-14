<?php

namespace Os\Framework\Config\Exception;

use Throwable;

class ConfigFileParsingException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(string $fileName, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("Error parsing config file '%s' (file extension not supported or not parsed correctly)", $fileName), previous: $previous);
    }
}