<?php

namespace Os\Framework\DependencyInjection\Exception;

use Throwable;

class LoadClassException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(protected string $className, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("Error loading class '%s'", $className), previous: $previous);
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }
}