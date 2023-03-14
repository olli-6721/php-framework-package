<?php

namespace Os\Framework\Filesystem\FileDefinition\Php;

use Os\Framework\DataAbstractionLayer\Service\DataType;

class PhpFunctionDefinition
{
    use PhpAttributeTrait;

    protected array $arguments;
    protected string $content;

    public function __construct(protected string $name, protected ?DataType $responseType = null, protected string $visibility = PhpClassDefinition::VISIBILITY_PUBLIC, protected bool $responseTypeNullable = false, protected bool $static = false){
        $this->arguments = [];
        $this->content = sprintf("//TODO: %s content", $name);
    }


    public function addArgument(PhpFunctionArgumentDefinition $argument): static
    {
        $this->arguments[$argument->getName()] = $argument;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return ?DataType
     */
    public function getResponseType(): ?DataType
    {
        return $this->responseType;
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
    public function isResponseTypeNullable(): bool
    {
        return $this->responseTypeNullable;
    }

    /**
     * @return PhpFunctionArgumentDefinition[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->static;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return PhpFunctionDefinition
     */
    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }
}