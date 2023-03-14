<?php

namespace Os\Framework\DataAbstractionLayer\Exception;

use Os\Framework\DataAbstractionLayer\Entity;
use Throwable;

class EntityHydrationException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(protected ?Entity $entity = null, ?Throwable $previous = null)
    {
        $baseMessage = "Entity hydration failed";
        if($this->entity !== null)
            $baseMessage = sprintf("%s, entity: %s_%s", $baseMessage, $this->entity::getTableName(), $this->entity->getId());
        parent::__construct(message: $baseMessage, previous: $previous);
    }
}