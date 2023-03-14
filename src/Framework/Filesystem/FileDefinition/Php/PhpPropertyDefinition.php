<?php

namespace Os\Framework\Filesystem\FileDefinition\Php;

use Os\Framework\DataAbstractionLayer\Service\DataType;
use Os\Framework\Exception\FrameworkException;

class PhpPropertyDefinition
{
    use PhpAttributeTrait;

    protected array $attributes;

    public function __construct(protected string $name, protected DataType $type, protected string $visibility = PhpClassDefinition::VISIBILITY_PROTECTED, protected bool $nullable = false)
    {
        $this->attributes = [];
    }

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
     * @return string
     */
    public function getVisibility(): string
    {
        return $this->visibility;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }
}