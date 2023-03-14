<?php

namespace Os\Framework\DataAbstractionLayer;

use Os\Framework\Config\ConfigReader;
use Os\Framework\DataAbstractionLayer\Driver\DriverInterface;

class EntityManager
{
    protected EntityHydrator $entityHydrator;

    /**
     * @throws \Exception
     */
    public function __construct(protected ConfigReader $configReader, protected DriverInterface $driver)
    {
        $this->entityHydrator = new EntityHydrator();
        $config = $this->configReader->read("dal", ["access"]);
        $accessData = $config["access"];
        if(!isset($accessData["type"]) || !isset($accessData["host"]) || !isset($accessData["database"]) || !isset($accessData["username"]) || !isset($accessData["password"]))
            throw new \Exception("No valid database connection configuration found");

        $this->driver->connect($accessData["type"], $accessData["host"], $accessData["database"], $accessData["username"], $accessData["password"]);
    }

    public function getHydrator(): EntityHydrator
    {
        return $this->entityHydrator;
    }

    /**
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * @throws \Exception
     */
    public function getRepository(string $entityClass): ?EntityRepository
    {
        try {
            if(!method_exists($entityClass, "getRepositoryClass"))
                throw new \Exception(sprintf("Method getRepositoryClass not found in class '%s'", $entityClass));
            $repositoryClass = call_user_func($entityClass .'::getRepositoryClass');
            return new $repositoryClass($this);
        }
        catch (\Throwable $e)
        {
            throw new \Exception(sprintf("Error initializing repository of entity '%s': %s", $entityClass, $e->getMessage()), 400, $e);
        }
    }

    public static function serialize(Entity $entity){}
}