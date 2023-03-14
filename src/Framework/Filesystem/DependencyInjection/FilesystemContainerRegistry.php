<?php

namespace Os\Framework\Filesystem\DependencyInjection;

use Os\Framework\DependencyInjection\ContainerRegistryInterface;
use Os\Framework\Filesystem\Command\FilesystemCacheClearCommand;

class FilesystemContainerRegistry implements ContainerRegistryInterface
{

    public static function getClasses(): array
    {
        return [
            FilesystemCacheClearCommand::class => null
        ];
    }
}