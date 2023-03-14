<?php

namespace Os\Framework\Filesystem\FileDefinition\Php;

class PhpClassDefinition
{
    use PhpAttributeTrait;

    public const VISIBILITY_PUBLIC = "public";
    public const VISIBILITY_PROTECTED = "protected";
    public const VISIBILITY_PRIVATE = "private";

    protected array $functions;
    protected array $properties;
    protected array $dependencies;
    protected ?string $extends;
    protected ?string $annotations;

    public function __construct(protected string $className, protected string $namespace){
        $this->functions = [];
        $this->properties = [];
        $this->dependencies = [];
        $this->extends = null;
        $this->annotations = null;
    }

    public function addFunction(PhpFunctionDefinition $function): static
    {
        $this->functions[$function->getName()] = $function;
        return $this;
    }

    public function addProperty(PhpPropertyDefinition $property): static
    {
        $this->properties[$property->getName()] = $property;
        return $this;
    }

    public function addDependency(string $className): static
    {
        if(in_array($className, $this->dependencies)) return $this;
        $this->dependencies[] = $className;
        return $this;
    }

    public function setExtends(string $className): static
    {
        $this->extends = $className;
        return $this;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return PhpFunctionDefinition[]
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * @return PhpPropertyDefinition[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @return string|null
     */
    public function getExtends(): ?string
    {
        return $this->extends;
    }

    /**
     * @return string|null
     */
    public function getAnnotations(): ?string
    {
        return $this->annotations;
    }

    /**
     * @param string|null $annotations
     */
    public function setAnnotations(?string $annotations): static
    {
        $this->annotations = $annotations;
        return $this;
    }
}