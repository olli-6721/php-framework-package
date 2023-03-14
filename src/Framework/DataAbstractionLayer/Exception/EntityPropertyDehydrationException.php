<?php

namespace Os\Framework\DataAbstractionLayer\Exception;

use Os\Framework\Exception\FrameworkException;
use Throwable;

class EntityPropertyDehydrationException extends FrameworkException
{
    public function __construct(protected string $property, protected string $entity, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("Error dehydrating %s.%s", $this->entity, $this->property), previous: $previous);
    }
}