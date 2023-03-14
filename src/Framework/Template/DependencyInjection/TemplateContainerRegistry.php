<?php

namespace Os\Framework\Template\DependencyInjection;

use Os\Framework\Template\Render\Function\BaseTemplateFunction;

class TemplateContainerRegistry implements \Os\Framework\DependencyInjection\ContainerRegistryInterface
{

    public static function getClasses(): array
    {
        return [
            BaseTemplateFunction::class => null
        ];
    }
}