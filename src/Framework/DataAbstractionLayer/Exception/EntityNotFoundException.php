<?php

namespace Os\Framework\DataAbstractionLayer\Exception;

use Throwable;

class EntityNotFoundException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(protected string $entityName, protected string $entityId, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("Entity with name %s and id %s was not found", $this->entityName, $this->entityId));
    }

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }
}