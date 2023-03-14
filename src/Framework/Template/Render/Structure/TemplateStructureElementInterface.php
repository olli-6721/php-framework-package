<?php

namespace Os\Framework\Template\Render\Structure;

interface TemplateStructureElementInterface
{
    public static function getElementName(): string;

    public function setArguments(array $arguments): static;

    public function resolve(string &$templateContent);
}