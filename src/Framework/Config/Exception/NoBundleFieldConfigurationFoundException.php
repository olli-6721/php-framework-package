<?php

namespace Os\Framework\Config\Exception;

use Throwable;

class NoBundleFieldConfigurationFoundException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(string $bundleName, string $fieldName, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("No configuration for '%s' in bundle '%s' found", $fieldName, $bundleName), previous: $previous);
    }
}