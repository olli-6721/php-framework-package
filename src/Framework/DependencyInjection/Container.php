<?php

namespace Os\Framework\DependencyInjection;

use Os\Framework\DataAbstractionLayer\DependencyInjection\DataAbstractionLayerContainerRegistry;
use Os\Framework\Debug\Dumper;
use Os\Framework\DependencyInjection\Autoload\Autoloader;
use Os\Framework\DependencyInjection\Compiler\Compiler;
use Os\Framework\DependencyInjection\Exception\CannotCallMethodException;
use Os\Framework\DependencyInjection\Instance\InstanceDefinition;
use Os\Framework\Config\ConfigReader;
use Os\Framework\Exception\FrameworkException;
use Os\Framework\Filesystem\Cache\FileBasedCacheProvider;

class Container implements ContainerInterface
{

    public function __construct(protected array $services = [], protected array $symlinks = [], protected array $groups = []){}

    public function set(string $id, InstanceDefinition $service): static
    {
        $this->services[$id] = $service;
        if($service->getAlias() !== null)
            $this->addSymlink($id, $service->getAlias());
        foreach($service->getGroups() as $group){
            $this->addGroupEntry($group, $id);
        }
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function get(string $id)
    {
        $serviceDefinition = $this->getDefinition($id);
        if($serviceDefinition === null)
            return null;
        $realId = $serviceDefinition["id"];
        /** @var InstanceDefinition $serviceDefinition */
        $serviceDefinition = $serviceDefinition["definition"];
        if(!$serviceDefinition->isInitialized()){
            $serviceDefinition->initialize($this);
            $this->services[$realId] = $serviceDefinition;
        }
        return $serviceDefinition->getInstance() ?? null;
    }

    /**
     * @throws \Exception
     */
    public function getByGroup(string $groupName): ?array
    {
        if(!isset($this->groups[$groupName])) return null;
        $ids = $this->groups[$groupName];
        $services = [];
        foreach($ids as $id){
            $service = $this->get($id);
            if($service === null) continue;
            $services[] = $service;
        }
        return $services;
    }

    public function getDefinition(string $id): ?array
    {
        $_id = $id;
        if(empty($this->services[$id])) {
            foreach($this->symlinks as $key => $symlinks){
                $id = in_array($id, array_values($symlinks), true);
                if($id === false){
                    $id = $_id;
                    continue;
                }
                $id = $key;
                break;
            }
            if($id === $_id) return null;
        }
        if(empty($this->services[$id])) return null;
        return ["id" => $id, "definition" => $this->services[$id]];
    }

    /**
     * @param object|string $class
     * @param \ReflectionMethod|string $method
     * @return void
     * @throws \ReflectionException
     * @throws CannotCallMethodException
     * @throws \Exception
     */
    public function callMethod(object|string $class, string|\ReflectionMethod $method, array $parameters = []){
        $autoloader = null;
        if(is_string($class)){
            $instance = $this->get($class) ?? null;
            if($instance === null){
                $autoloader = new Autoloader($this);
                $definition = $autoloader->loadClass($class);
                if(!$definition->isInitialized())
                    $definition->initialize($this);
                $instance = $definition->getInstance();
            }
            $class = $instance;
        }
        if(is_string($method)){
            $method = new \ReflectionMethod($class, $method);
        }
        $methodName = $method->getName();
        $_parameters = [];
        foreach($method->getParameters() as $parameterDefinition){
            $parameterName = $parameterDefinition->getName();
            $parameterType = $parameterDefinition->getType()->getName();
            if(isset($parameters[$parameterName])){
                $_parameters[$parameterDefinition->getPosition()] = $parameters[$parameterName];
                continue;
            }
            if(in_array($parameterType, Autoloader::FORBIDDEN_PARAMETER_TYPES)){
                $parameters[$parameterDefinition->getPosition()] = $parameterDefinition->getDefaultValue();
                continue;
            }
            $instance = $this->get($parameterType) ?? null;
            if($instance === null){
                if($autoloader === null)
                    $autoloader = new Autoloader($this);
                $definition = $autoloader->loadClass($parameterType);
                if(!$definition->isInitialized())
                    $definition->initialize($this);
                $instance = $definition->getInstance();
            }
            $_parameters[$parameterDefinition->getPosition()] = $instance;
        }
        try {
            return $class->$methodName(...$_parameters);
        }
        catch (\Throwable $e){
            throw new CannotCallMethodException($class::class, $methodName, $e);
        }
    }

    public function has(string $id): bool
    {
        return !empty($this->services[$id]);
    }

    public function destroy(){
        /** @var InstanceDefinition $service */
        foreach($this->services as $service){
            $service->destroyInstance();
        }
        $this->services = [];
    }

    public function isInitialized(string $id): ?bool
    {
        $serviceDefinition = $this->getDefinition($id);
        if($serviceDefinition === null)
            return null;
        return $serviceDefinition["definition"]->isInitialized();
    }

    /**
     * @throws \Exception
     */
    public static function build(array $services = []): static
    {
        if(isset($services[ConfigReader::class]))
            $configReader = $services[ConfigReader::class]?->getInstance() ?? new ConfigReader();
        else
            $configReader = new ConfigReader();

        $autoloadDirectories = $configReader->readPath("di.autoload") ?? [];
        $serviceDefinitions = $configReader->readPath("di.services") ?? [];


        if(ENV === "DEV" || !FileBasedCacheProvider::exists("service_container")){
            $instance = self::loadContainer($services, $autoloadDirectories, $serviceDefinitions);
            if(ENV !== "DEV"){
                FileBasedCacheProvider::set("service_container", $instance);
            }
        }
        else {
            $instance = FileBasedCacheProvider::get("service_container");
            if($instance === null)
                $instance = self::loadContainer($services, $autoloadDirectories, $serviceDefinitions);
        }
        return $instance;
    }

    /**
     * @throws FrameworkException
     */
    protected static function loadContainer(array $services, array $autoloadDirectories, array $serviceDefinitions): Container
    {
        $instance = new self($services);

        $compiler = new Compiler($instance);
        $compiler->compile($serviceDefinitions, $autoloadDirectories);
        return $instance;
    }

    public function addSymlink(string $existingId, string $symlinkId): static
    {
        if(!$this->has($existingId)) return $this;
        if(!isset($this->symlinks[$existingId])) $this->symlinks[$existingId] = [];
        $this->symlinks[$existingId][] = $symlinkId;
        return $this;
    }

    public function addGroupEntry(string $groupName, string $id): static
    {
        if(!isset($this->groups[$groupName])) $this->groups[$groupName] = [];
        if(!in_array($id, $this->groups[$groupName])) $this->groups[$groupName][] = $id;
        return $this;
    }
}