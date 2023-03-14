<?php

namespace Os\Framework\Template\Render\Structure;

class ExtendsTemplateStructureElement implements TemplateStructureElementInterface
{
    protected array $arguments = [];

    public function __construct(){}

    public static function getElementName(): string
    {
        return "extends";
    }

    public function setArguments(array $arguments): static
    {

        return $this;
    }

    public function resolve(string &$templateContent)
    {
        // TODO: Implement resolve() method.
    }
}