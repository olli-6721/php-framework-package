<?php

namespace Os\Framework\DataAbstractionLayer;

class EntityCollection
{
    protected array $entities;

    public function __construct(array $entities = [])
    {
        $this->entities = [];
        foreach($entities as $entity){
            if(!($entity instanceof Entity)) throw new \Exception(sprintf("Entities have to extend the '%s' class", Entity::class));
            $this->entities[$entity->getId()] = $entity;
        }
    }

    public function get(string $id): ?Entity
    {
        return $this->entities[$id] ?? null;
    }

    public function first(): ?Entity
    {
        $key = array_key_first($this->entities);
        return $this->entities[$key] ?? null;
    }

    public function add(Entity $entity): static
    {
        $this->entities[$entity->getId()] = $entity;
        return $this;
    }

    public function remove(string $id): static
    {
        if(isset($this->entities[$id]))
            unset($this->entities[$id]);
        return $this;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }
}