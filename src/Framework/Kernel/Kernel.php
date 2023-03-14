<?php

namespace Os\Framework\Kernel;

use Os\Framework\Debug\Dumper;
use Os\Framework\DependencyInjection\Container;
use Os\Framework\DependencyInjection\ContainerInterface;
use Os\Framework\Config\ConfigReader;
use Os\Framework\DependencyInjection\Instance\InstanceDefinition;
use Os\Framework\Exception\Handler\ExceptionHandler;
use Os\Framework\Filesystem\Cache\FileBasedCacheProvider;
use Os\Framework\Http\Request\Request;
use Os\Framework\Http\Response\Response;
use Os\Framework\Http\Routing\Router;

abstract class Kernel implements KernelInterface
{
    protected ?Response $response;
    protected ?ContainerInterface $container;
    protected ConfigReader $configReader;

    protected float $requestInitialization = INITIALIZATION_START;
    protected ?float $buildTime;
    protected ?float $renderTime;
    protected ?float $executionTime;

    abstract protected function _render();
    abstract protected function _done();

    /**
     * @throws \Exception
     */
    final private function __construct(){
        try {
            $this->container = null;
            $start = microtime(true) * 1000;
            define("FRAMEWORK_BASE_PATH", realpath(dirname(__DIR__)));
            $this->___construct();
            $this->buildTime = (microtime(true) * 1000) - $start;
        }
        catch (\Throwable $e){
            new ExceptionHandler($e, $this->container);
        }
    }

    final public static function build(): static
    {
        return new static();
    }

    final public function render()
    {
        try {
            $start = microtime(true) * 1000;
            $this->_render();
            $this->renderTime = (microtime(true) * 1000) - $start;
        }
        catch (\Throwable $e){
            new ExceptionHandler($e, $this->container);
        }
    }

    public function done()
    {
        try {
            $this->_done();
            $this->executionTime = (microtime(true) * 1000) - $this->requestInitialization;
        }
        catch (\Throwable $e){
            new ExceptionHandler($e, $this->container);
        }
    }

    protected function ___construct(){
        $this->configReader = new ConfigReader();
        $this->response = null;

        $_crInstance = new InstanceDefinition(ConfigReader::class, []);
        $_crInstance->initialize();

        $containerBaseServices = [ConfigReader::class => $_crInstance];

        $env = $this->configReader->getAppEnv();
        define("ENV", strtoupper($env));
        if(ENV === "DEV")
            FileBasedCacheProvider::clear();
        $config = $this->configReader->read("kernel");
        $containerClass = $config["container"];

        if(FileBasedCacheProvider::exists('service_container')){
            $this->container = FileBasedCacheProvider::get('service_container');
        }
        else {
            try {
                $reflectionClass = new \ReflectionClass($containerClass);
                if(!in_array(ContainerInterface::class, $reflectionClass->getInterfaceNames())) throw new \Exception(sprintf("Container-Class '%s' is not a member of %s", $containerClass, ContainerInterface::class));
                $this->container = $containerClass::build($containerBaseServices);
            }
            catch (\Throwable $e){
                $this->container = Container::build($containerBaseServices);
            }
            if(ENV !== "DEV") FileBasedCacheProvider::set('service_container', $this->container);
        }

    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return float
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }
}