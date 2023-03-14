<?php

namespace Os\Framework\Template\Render\Variable;

use Os\Framework\DataAbstractionLayer\Service\DataType;

class TemplateVariable
{
    public function __construct(protected string $name, protected ?DataType $type, protected string|bool|int|float $value){}

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return DataType
     */
    public function getType(): DataType
    {
        return $this->type;
    }

    /**
     * @return bool|float|int|string
     */
    public function getValue(): float|bool|int|string
    {
        return $this->value;
    }


}