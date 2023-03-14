<?php

namespace Os\Framework\DataAbstractionLayer;

use JetBrains\PhpStorm\Pure;
use Os\Framework\DataAbstractionLayer\Driver\DriverInterface;
use Os\Framework\DataAbstractionLayer\Driver\PdoDriver;
use Os\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Os\Framework\Debug\Dumper;
use Os\Framework\Exception\MethodNotFoundException;

abstract class EntityRepository
{
    abstract public static function getEntityClass(): string;

    protected EntityHydrator $entityHydrator;
    protected DriverInterface $driver;
    
    #[Pure]
    final public function __construct(protected EntityManager $entityManager)
    {
        $this->entityHydrator = $this->entityManager->getHydrator();
        $this->driver = $this->entityManager->getDriver();
    }

    public static function getEntityCollectionClass(): string {return EntityCollection::class;}

    /**
     * @throws EntityNotFoundException
     * @throws Exception\EntityDehydrationException
     * @throws MethodNotFoundException
     * @throws Exception\EntityHydrationException
     */
    public function update(Entity &$entity): ?Entity
    {
        $origin = $this->find($entity->getId());
        if($origin === null)
            throw new EntityNotFoundException($entity::getTableName(), $entity->getId());
        $entity->setUpdatedAt(new \DateTime());

        $originArray = $this->entityHydrator->dehydrate($origin);
        $entityArray = $this->entityHydrator->dehydrate($entity);

        $differences = ["id" => $entity->getId()];
        foreach($originArray as $key => $value){
            if(!isset($entityArray[$key])) continue;
            if($value === $entityArray[$key]) continue;
            $differences[$key] = $entityArray[$key];
        }

        $this->driver->update($entity::getTableName(), $differences, "`id` = :id");

        $entity = $this->find($entity->getId());
        return $entity;
    }


    /**
     * @throws MethodNotFoundException
     * @throws Exception\EntityHydrationException
     * @throws Exception\EntityDehydrationException
     */
    public function create(Entity &$entity): ?Entity
    {
        $entity->setCreatedAt(new \DateTime());
        $entityArray = $this->entityHydrator->dehydrate($entity);
        $this->driver->create($entity::getTableName(), $entityArray);

        $entity = $this->find($entity->getId());
        return $entity;
    }


    /**
     * @throws MethodNotFoundException
     * @throws Exception\EntityHydrationException
     */
    public function find(string $id): ?Entity
    {
        return $this->findBy(["id" => $id], 1)->first();
    }

    /**
     * @throws MethodNotFoundException
     * @throws Exception\EntityHydrationException
     */
    public function findOneBy(array $criteria): ?Entity
    {
        return $this->findBy($criteria, 1)->first();
    }

    /**
     * @throws MethodNotFoundException
     * @throws Exception\EntityHydrationException
     */
    public function findBy(array $criteria, ?int $limit = 100): ?EntityCollection
    {
        $where = "";
        $last = array_key_last($criteria);
        foreach($criteria as $key => $criteriaEntry) {
            if($key === $last){
                $where = sprintf("%s `%s` = :%s", $where, PdoDriver::toSnakeCase($key), $key);
            }
            else {
                $where = sprintf("%s `%s` = :%s AND", $where, PdoDriver::toSnakeCase($key), $key);
            }
        }
        $result = $this->driver->select(from: $this->getEntityTableName(), where: trim($where), parameters: $criteria, limit: $limit);
        return empty($result) ? new EntityCollection() : $this->entityHydrator->hydrate($result, $this);
    }

    /**
     * @throws MethodNotFoundException
     */
    protected function getEntityTableName(): string {
        $entityClass = static::getEntityClass();
        if(!method_exists($entityClass, "getTableName"))
            throw new MethodNotFoundException("getTableName", $entityClass);
        return call_user_func(sprintf("%s::%s", $entityClass, "getTableName"));
    }
}