<?php

namespace Os\Framework\DataAbstractionLayer;

use Os\Framework\DataAbstractionLayer\Attribute\Column;
use Os\Framework\DataAbstractionLayer\Driver\PdoDriver;
use Os\Framework\DataAbstractionLayer\Exception\EntityDehydrationException;
use Os\Framework\DataAbstractionLayer\Exception\EntityHydrationException;
use Os\Framework\DataAbstractionLayer\Exception\EntityPropertyDehydrationException;
use Os\Framework\DataAbstractionLayer\Exception\EntityPropertyHydrationException;
use Os\Framework\DataAbstractionLayer\Exception\PropertyNotNullableException;
use Os\Framework\Debug\Dumper;
use ReflectionException;

class EntityHydrator
{
    protected const DATE_FORMAT = "Y-m-d H:i:s";

    /**
     * @throws EntityHydrationException
     */
    public function hydrate(array $data, EntityRepository $repository): Entity|EntityCollection
    {
        try {
            $instance = null;
            $entityClass = $repository::getEntityClass();
            if(is_numeric(array_key_first($data))){
                $collection = $this->getCollectionInstance($repository);
                foreach($data as $entity){
                    $instance = $this->hydrateEntity($entity, $entityClass);
                    $collection->add($instance);
                }
                return $collection;
            }
            else {
                $instance = $this->hydrateEntity($data, $entityClass);
                return $instance;
            }
        }
        catch (\Throwable $e){
            if($instance instanceof Entity || $instance === null)
                throw new EntityHydrationException(entity: $instance, previous: $e);
            throw new EntityHydrationException(previous: $e);
        }
    }

    /**
     * @throws ReflectionException
     * @throws EntityHydrationException
     */
    protected function hydrateEntity(array $data, string $entityClass): Entity
    {
        $reflectionInstance = new \ReflectionClass($entityClass);
        /** @var Entity $instance */
        $instance = new $entityClass();
        foreach($reflectionInstance->getProperties() as $reflectionProperty){
            try {
                $propertyName = $reflectionProperty->getName();
                if(empty($data[PdoDriver::toSnakeCase($propertyName)]) && $reflectionProperty?->getType()?->allowsNull())
                    $data[PdoDriver::toSnakeCase($propertyName)] = null;
                if(!array_key_exists(PdoDriver::toSnakeCase($propertyName), $data)) continue;

                $this->hydrateProperty($instance, $reflectionProperty, $data[PdoDriver::toSnakeCase($propertyName)]);
            }
            catch (\Throwable $e){
                throw new EntityHydrationException($instance, $e);
            }
        }
        return $instance;
    }

    /**
     * @throws EntityPropertyHydrationException
     */
    protected function hydrateProperty(Entity &$entityInstance, \ReflectionProperty $property, $value){
        $data = $value;
        try {

            switch ($property->getType()->getName()){
                case "array":
                case "object":
                    $data = json_decode($value);
                    break;
                case \DateTimeInterface::class:
                case \DateTime::class:
                    if($value === null && $property->getType()->allowsNull()) break;
                    $data = \DateTime::createFromFormat(self::DATE_FORMAT, $value);
                    if($data === false)
                        throw new \Exception(sprintf("Cant create DateTimeObject from value '%s' field: '%s'", json_encode($value), $property->getName()));
                    break;
            }

            $property->setAccessible(true);
            $property->setValue($entityInstance,$data);
        }
        catch (\Throwable $e){
            throw new EntityPropertyHydrationException($property->getName(), $entityInstance::getTableName(), $e);
        }
    }

    /**
     * @throws EntityDehydrationException
     */
    public function dehydrate(Entity|EntityCollection $data): array
    {
        try {
            if($data instanceof EntityCollection){
                $collectionArray = [];
                foreach($data->getEntities() as $entity){
                    $collectionArray[] = $this->dehydrateEntity($entity);
                }
                return $collectionArray;
            }
            else {
                return $this->dehydrateEntity($data);
            }
        }
        catch (\Throwable $e){
            throw new EntityDehydrationException($data, $e);
        }
    }

    protected function dehydrateEntity(Entity $entity): array
    {
        $reflectionInstance = new \ReflectionClass($entity);
        $entityArray = [];
        foreach($reflectionInstance->getProperties() as $reflectionProperty){
            $this->dehydrateProperty($entityArray, $reflectionProperty, $entity);
        }
        return $entityArray;
    }

    protected function dehydrateProperty(array &$entityArray, \ReflectionProperty $property, Entity $originEntity){
        try {
            $property->setAccessible(true);
            $data = $property->getValue($originEntity);

            switch ($property->getType()->getName()){
                case "array":
                case "object":
                    $data = json_encode($data);
                    break;
                case \DateTimeInterface::class:
                case \DateTime::class:
                    if($data === null && !$property->getType()->allowsNull())
                        throw new PropertyNotNullableException($property->getName(), $originEntity::class);
                    if($data === null)
                        break;
                    $data = $data->format(self::DATE_FORMAT);
                    break;
            }

            $entityArray[PdoDriver::toSnakeCase($property->getName())] = $data;

        }
        catch (\Throwable $e){
            throw new EntityPropertyDehydrationException($property->getName(), $originEntity::getTableName());
        }
    }

    protected function getCollectionInstance(EntityRepository $repository): EntityCollection
    {
        $collectionClass = $repository::getEntityCollectionClass();
        return new $collectionClass();
    }
}