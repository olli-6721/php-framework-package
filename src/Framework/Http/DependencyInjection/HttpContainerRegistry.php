<?php

namespace Os\Framework\Http\DependencyInjection;

use Os\Framework\Http\Command\HttpControllerCreateCommand;

class HttpContainerRegistry implements \Os\Framework\DependencyInjection\ContainerRegistryInterface
{

    public static function getClasses(): array
    {
        return [
            HttpControllerCreateCommand::class => null
        ];
    }
}