<?php

namespace Os\Framework\DataAbstractionLayer\Attribute;

use Attribute;
use Os\Framework\DataAbstractionLayer\Service\DataType;
use Os\Framework\Exception\FrameworkException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(protected DataType $type, protected bool $unique = false)
    {
    }

    /**
     * @return DataType
     */
    public function getType(): DataType
    {
        return $this->type;
    }


    /**
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }
}