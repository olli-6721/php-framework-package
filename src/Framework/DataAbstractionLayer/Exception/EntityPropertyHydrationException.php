<?php

namespace Os\Framework\DataAbstractionLayer\Exception;

use JetBrains\PhpStorm\Pure;
use Throwable;

class EntityPropertyHydrationException extends \Os\Framework\Exception\FrameworkException
{
    #[Pure]
    public function __construct(protected string $property, protected string $entity, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("Error hydrating %s.%s", $this->entity, $this->property), previous: $previous);
    }
}