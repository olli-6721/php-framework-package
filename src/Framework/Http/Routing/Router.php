<?php

namespace Os\Framework\Http\Routing;

use Exception;
use Os\Framework\Config\ConfigReader;
use Os\Framework\Config\Exception\ConfigurationViolationException;
use Os\Framework\Filesystem\Cache\FileBasedCacheProvider;
use Os\Framework\Http\Controller\AbstractController;
use Os\Framework\Debug\Dumper;
use Os\Framework\DependencyInjection\Autoload\Autoloader;
use Os\Framework\DependencyInjection\ContainerInterface;
use Os\Framework\DependencyInjection\Instance\InstanceDefinition;
use Os\Framework\Exception\FrameworkException;
use Os\Framework\Http\Exception\RouteNotFoundException;
use Os\Framework\Http\Request\Request;
use Os\Framework\Http\Response\Response;
use Os\Framework\Http\Routing\Attribute\Route;
use Os\Framework\Http\Exception\ControllerRouteLoadingFailedException;
use ReflectionMethod;

class Router
{
    protected Autoloader $autoloader;
    protected ConfigReader $configReader;
    protected array $controller;
    protected array $routes;

    /**
     * @throws FrameworkException
     * @throws Exception
     */
    public function __construct(protected ContainerInterface $container)
    {
        $this->autoloader = new Autoloader($this->container);
        $this->configReader = $this->container->get(ConfigReader::class);
        $this->routes = (FileBasedCacheProvider::exists("http_routes") && FileBasedCacheProvider::exists("http_controllers")) ? $this->loadControllerRoutesFromCache(FileBasedCacheProvider::get("http_routes"), FileBasedCacheProvider::get('http_controllers')) : [];
        $this->controller = [];
        if(empty($this->routes)){
            $controllerLocationConfig = $this->configReader->readPath("http.paths.controller");
            if($controllerLocationConfig === null)
                throw new FrameworkException("No controller location given in 'http' configuration");
            if(!is_array($controllerLocationConfig) || count($controllerLocationConfig) < 2)
                throw new ConfigurationViolationException("http.paths.controller", "Controller location-data must contain an array with two indices");
            $this->loadControllerRoutesFromDirectory($controllerLocationConfig[0]);
        }
    }

    /**
     * @throws \ReflectionException
     * @throws FrameworkException
     * @throws RouteNotFoundException
     */
    public function resolve(Request $request): Response
    {
        $response = null;
        /** @var \Os\Framework\Http\Routing\Route\Route $route */
        foreach($this->routes as $route)
        {
            $instructions = $route->resolve($request);
            if($instructions === null) continue;
            $controllerReflectionClass = $instructions->getMethod()->getDeclaringClass();
            $controller = $this->container->get($controllerReflectionClass->getName()) ?? $this->autoloader->loadClass($controllerReflectionClass);
            if($controller instanceof InstanceDefinition){
                if(!$controller->isInitialized())
                    $controller->initialize($this->container);
                $controller = $controller->getInstance();
            }
            $response = $this->container->callMethod($controller, $instructions->getMethod(), $instructions->getParameters()) ?? new Response();
            if(!($response instanceof Response))
                $response = new Response();
            break;
        }
        if($response === null)
            throw new RouteNotFoundException();
        return $response;
    }

    /**
     * @throws Exception
     */
    protected function loadControllerRoutesFromDirectory(string $directory){
        $classes = $this->autoloader->loadDirectory($directory);
        $cachedRoutes = [];
        /**
         * @var InstanceDefinition $instanceDefinition
         */
        foreach($classes as $instanceDefinition){
            try {
                if(!isset(class_parents($instanceDefinition->getClassName())[AbstractController::class])) continue;
                $reflectionClass = new \ReflectionClass($instanceDefinition->getClassName());
                foreach($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod)
                {
                    $routeAttributes = [];
                    $attributes = $reflectionMethod->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF);
                    if(empty($attributes)) continue;
                    foreach($attributes as $attribute){
                        $routeAttributes[] = $attribute->newInstance();
                    }
                    $this->routes[] = new \Os\Framework\Http\Routing\Route\Route($routeAttributes, $reflectionMethod);
                    $cachedRoutes[] = [
                        "controllerClass" => $instanceDefinition->getClassName(),
                        "methodName" => $reflectionMethod->getName(),
                        "attributes" => $routeAttributes
                    ];
                }
            }
            catch (\Throwable $e){
                throw new ControllerRouteLoadingFailedException($instanceDefinition->getClassName(), $e);
            }
        }
        FileBasedCacheProvider::delete("http_routes");
        FileBasedCacheProvider::set("http_routes", $cachedRoutes);
        FileBasedCacheProvider::set("http_controllers", $classes);
    }

    protected function loadControllerRoutesFromCache(array $routeData, array $controllers): array
    {
        $routes = [];
        foreach($routeData as $route){
            $controllerClassName = $route["controllerClass"];
            $instance = null;
            foreach($controllers as $controller){
                if($controller::class === $controllerClassName){
                    $instance = $controller;
                    break;
                }
            }
            if($instance === null) continue;

            $reflectionMethod = new ReflectionMethod($instance, $route["methodName"]);
            $routes[] = new \Os\Framework\Http\Routing\Route\Route($route["attributes"], $reflectionMethod);
        }

        return $routes;
    }
}