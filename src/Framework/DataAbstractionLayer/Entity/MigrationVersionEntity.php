<?php

namespace Os\Framework\DataAbstractionLayer\Entity;

use Os\Framework\DataAbstractionLayer\Attribute\Column;
use Os\Framework\DataAbstractionLayer\Service\DataType;

class MigrationVersionEntity extends \Os\Framework\DataAbstractionLayer\Entity
{

    #[Column(DataType::INT)]
    protected int $versionTimestamp;
    #[Column(DataType::STRING)]
    protected string $class;

    public static function getRepositoryClass(): string
    {
        return MigrationVersionRepository::class;
    }

    /**
     * @return int
     */
    public function getVersionTimestamp(): int
    {
        return $this->versionTimestamp;
    }

    /**
     * @param int $versionTimestamp
     * @return MigrationVersionEntity
     */
    public function setVersionTimestamp(int $versionTimestamp): MigrationVersionEntity
    {
        $this->versionTimestamp = $versionTimestamp;
        return $this;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     * @return MigrationVersionEntity
     */
    public function setClass(string $class): MigrationVersionEntity
    {
        $this->class = $class;
        return $this;
    }
}