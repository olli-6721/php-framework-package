<?php

namespace Os\Framework\DataAbstractionLayer\Migration;

use Os\Framework\Config\ConfigReader;
use Os\Framework\DataAbstractionLayer\Driver\DriverInterface;

abstract class AbstractMigration
{
    public function __construct(protected DriverInterface $driver, protected ConfigReader $configReader){
        $config = $this->configReader->read("dal", ["access"]);
        $accessData = $config["access"];
        if(!isset($accessData["type"]) || !isset($accessData["host"]) || !isset($accessData["database"]) || !isset($accessData["username"]) || !isset($accessData["password"]))
            throw new \Exception("No valid database connection configuration found");

        $this->driver->connect($accessData["type"], $accessData["host"], $accessData["database"], $accessData["username"], $accessData["password"]);
    }

    abstract public static function getTimestamp(): int;

    abstract public function execute();

    abstract public function destroy();

    abstract public static function getEntityClass(): ?string;
}