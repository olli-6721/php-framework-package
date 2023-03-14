<?php

namespace Os\Framework\Config\Exception;

use Throwable;

class NoBundleConfigurationFoundException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(string $bundleName, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("No configuration for '%s' found", $bundleName), previous: $previous);
    }
}