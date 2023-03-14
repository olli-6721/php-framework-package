<?php

namespace Os\Framework\DataAbstractionLayer\Entity;

class MigrationVersionRepository extends \Os\Framework\DataAbstractionLayer\EntityRepository
{

    public static function getEntityClass(): string
    {
        return MigrationVersionEntity::class;
    }
}