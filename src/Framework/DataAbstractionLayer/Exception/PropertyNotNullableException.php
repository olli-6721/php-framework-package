<?php

namespace Os\Framework\DataAbstractionLayer\Exception;

use Throwable;

class PropertyNotNullableException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(protected ?string $propertyName, protected ?string $className = null, ?Throwable $previous = null)
    {
        $message = sprintf("Property '%s' is not nullable", $this->propertyName);
        if($this->className !== null)
            $message = sprintf("%s in class '%s'", $message, $this->className);
        parent::__construct(message: $message, previous: $previous);
    }
}