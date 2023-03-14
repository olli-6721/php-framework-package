<?php

namespace Os\Framework\Logger\DependencyInjection;

use Os\Framework\Logger\Adapter\FileBasedLogger;

class LoggerContainerRegistry implements \Os\Framework\DependencyInjection\ContainerRegistryInterface
{

    public static function getClasses(): array
    {
        return [
            FileBasedLogger::class => null
        ];
    }
}