<?php

namespace Os\Framework\DependencyInjection\Exception;

use Throwable;

class CannotCallMethodException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(string $className, string $methodName, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("Cannot call method %s of class %s", $methodName, $className), previous: $previous);
    }
}