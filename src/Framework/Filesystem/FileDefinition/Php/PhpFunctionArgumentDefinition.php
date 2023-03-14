<?php

namespace Os\Framework\Filesystem\FileDefinition\Php;

use Os\Framework\DataAbstractionLayer\Service\DataType;

class PhpFunctionArgumentDefinition
{
    public function __construct(protected string $name, protected ?DataType $dataType = null, protected bool $isNullable = false){}

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return DataType|null
     */
    public function getDataType(): ?DataType
    {
        return $this->dataType;
    }
}