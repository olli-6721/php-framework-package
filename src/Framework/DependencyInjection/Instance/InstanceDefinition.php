<?php
namespace Os\Framework\DependencyInjection\Instance;

use Os\Framework\Debug\Dumper;
use Os\Framework\DependencyInjection\ContainerInterface;

class InstanceDefinition {

    protected bool $initialized;
    protected ?object $instance;

    public function __construct(protected string $className, protected array $parameters = [], protected ?string $alias = null, protected array $groups = []){
        $this->initialized = false;
        $this->instance = null;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function initialize(ContainerInterface &$container = null){
        try {
            $class = $this->className;
            if(empty($this->parameters)){
                $this->instance = new $class();
                $this->initialized = true;
                return null;
            }
            $parameters = [];
            $containerKeys = [];
            /** @var InstanceDefinition|ContainerInstanceDefinition $parameterDefinition */
            foreach($this->parameters as $key => $parameterDefinition){
                if($parameterDefinition instanceof ContainerInstanceDefinition){
                    $containerKeys[] = $key;
                    $parameters[$key] = null;
                    continue;
                }
                if($parameterDefinition instanceof InstanceDefinition){
                    $service = $container->get($this->parameters[$key]->getClassName());
                    if($service === null){
                        if(!$this->parameters[$key]->isInitialized())
                            $this->parameters[$key]->initialize($container);
                        $service = $this->parameters[$key]->getInstance();
                        $container->set($this->parameters[$key]->getClassName(), $this->parameters[$key]);
                    }
                    $parameters[$key] = $service;
                }
            }

            foreach($containerKeys as $containerKey){
                if($container === null)
                    throw new \Exception("Container required but not given");
                $parameters[$containerKey] = $container;
            }

            $this->instance = new $class(...$parameters);
            $this->initialized = true;
        }
        catch (\Throwable $e){
            dd($e);
        }
    }

    public function destroyInstance(){
        $this->instance = null;
        $this->initialized = false;
    }

    /**
     * @return object|null
     */
    public function getInstance(): ?object
    {
        return $this->instance;
    }

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

}