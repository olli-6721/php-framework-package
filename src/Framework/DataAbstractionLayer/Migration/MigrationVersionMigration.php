<?php

namespace Os\Framework\DataAbstractionLayer\Migration;

use Os\Framework\DataAbstractionLayer\Entity\MigrationVersionEntity;

class MigrationVersionMigration extends AbstractMigration
{

    public static function getTimestamp(): int
    {
        return 1673431350;
    }

    public function execute()
    {
        $this->driver->execute(sprintf("CREATE TABLE IF NOT EXISTS %s 
        (
            `id` VARCHAR(255) UNIQUE NOT NULL, 
            `version_timestamp` INT NOT NULL, 
            `class` VARCHAR (255) NOT NULL,
            `created_at` DATETIME NOT NULL, 
            `updated_at` DATETIME
        )", MigrationVersionEntity::getTableName()));
    }

    public function destroy()
    {
        // TODO: Implement destroy() method.
    }

    public static function getEntityClass(): ?string
    {
        return MigrationVersionEntity::class;
    }
}