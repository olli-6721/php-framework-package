<?php

namespace Os\Framework\DataAbstractionLayer;

use Os\Framework\DataAbstractionLayer\Attribute\Column;
use Os\Framework\DataAbstractionLayer\Service\DataType;
use Os\Framework\DataAbstractionLayer\Service\Uuid;
use Os\Framework\Debug\Dumper;

abstract class Entity
{
    private string $_uniqueIdentifier;

    #[Column(DataType::UUID, true)]
    protected ?string $id;

    #[Column(DataType::DATETIME)]
    protected \DateTimeInterface $createdAt;

    #[Column(DataType::DATETIME)]
    protected ?\DateTimeInterface $updatedAt;

    abstract public static function getRepositoryClass(): string;

    final public static function getTableName(): string
    {
        $name = (new \ReflectionClass(static::class))->getShortName();
        if(str_ends_with($name, "Entity"))
            $name = substr($name, 0, -6);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    final public function __construct(){
        $this->_uniqueIdentifier = Uuid::v4();
        $reflection = new \ReflectionClass($this);
        foreach($reflection->getProperties() as $property){
            if(!$property->isProtected()) continue;
            $propertyName = $property->getName();
            if($property->getType()->allowsNull()){
                $this->$propertyName = null;
            }
        }
        if($this->id === null)
            $this->id = $this->_uniqueIdentifier;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id ?? $this->_uniqueIdentifier;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeInterface $createdAt
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTimeInterface|null $updatedAt
     */
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}