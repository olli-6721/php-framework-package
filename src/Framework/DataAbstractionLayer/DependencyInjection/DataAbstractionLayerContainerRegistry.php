<?php

namespace Os\Framework\DataAbstractionLayer\DependencyInjection;

use Os\Framework\DataAbstractionLayer\Command\DalEntityCreateCommand;
use Os\Framework\DataAbstractionLayer\Command\DalMigrationsCreateCommand;
use Os\Framework\DataAbstractionLayer\Command\DalMigrationsMigrateCommand;
use Os\Framework\DataAbstractionLayer\Driver\DriverInterface;
use Os\Framework\DataAbstractionLayer\Driver\PdoDriver;
use Os\Framework\DataAbstractionLayer\Entity\MigrationVersionRepository;
use Os\Framework\DataAbstractionLayer\EntityManager;
use Os\Framework\DataAbstractionLayer\Migration\MigrationVersionMigration;
use Os\Framework\DependencyInjection\ContainerRegistryInterface;

class DataAbstractionLayerContainerRegistry implements ContainerRegistryInterface
{
    public static function getClasses(): array
    {
        return [
            DalMigrationsMigrateCommand::class => null,
            DalMigrationsCreateCommand::class => null,
            DalEntityCreateCommand::class => null,
            MigrationVersionRepository::class => null,
            MigrationVersionMigration::class => null,
            EntityManager::class => null,
            PdoDriver::class => DriverInterface::class
        ];
    }
}