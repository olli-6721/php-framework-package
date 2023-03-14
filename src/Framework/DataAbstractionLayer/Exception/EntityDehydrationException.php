<?php

namespace Os\Framework\DataAbstractionLayer\Exception;

use Os\Framework\DataAbstractionLayer\Entity;
use Os\Framework\DataAbstractionLayer\EntityCollection;
use Throwable;

class EntityDehydrationException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(protected Entity|EntityCollection $data, ?Throwable $previous = null)
    {
        if($this->data instanceof EntityCollection){
            $message = sprintf("Error dehydrating collection '%s'", json_encode($this->data->getEntities()));
        }
        else {
            $message = sprintf("Error dehydrating entity %s_%s", $this->data::getTableName(), $this->data->getId());
        }
        parent::__construct(message: $message, previous: $previous);
    }
}