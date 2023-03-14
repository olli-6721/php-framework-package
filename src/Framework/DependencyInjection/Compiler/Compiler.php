<?php

namespace Os\Framework\DependencyInjection\Compiler;

use Os\Framework\DataAbstractionLayer\DependencyInjection\DataAbstractionLayerContainerRegistry;
use Os\Framework\DependencyInjection\Autoload\Autoloader;
use Os\Framework\DependencyInjection\ContainerInterface;
use Os\Framework\Debug\Dumper;
use Os\Framework\DependencyInjection\ContainerRegistryInterface;
use Os\Framework\Exception\FrameworkException;
use Os\Framework\Filesystem\DependencyInjection\FilesystemContainerRegistry;
use Os\Framework\Http\DependencyInjection\HttpContainerRegistry;
use Os\Framework\Logger\DependencyInjection\LoggerContainerRegistry;
use Os\Framework\Template\DependencyInjection\TemplateContainerRegistry;

class Compiler
{
    public const STATIC_REGISTRY = [
        DataAbstractionLayerContainerRegistry::class,
        TemplateContainerRegistry::class,
        HttpContainerRegistry::class,
        FilesystemContainerRegistry::class,
        LoggerContainerRegistry::class
    ];

    protected Autoloader $autoloader;

    /**
     * @throws FrameworkException
     */
    public function __construct(protected ContainerInterface &$containerInstance){
        $this->autoloader = new Autoloader($this->containerInstance);
    }

    public function compile(array $serviceDefinitions, array $autoloadDirectories)
    {
        /** @var ContainerRegistryInterface $registryClass */
        foreach(self::STATIC_REGISTRY as $registryClass) {
            $classes = $registryClass::getClasses();
            foreach($classes as $id => $alias){
                if(isset($serviceDefinition[$id])) continue;
                $serviceDefinitions[$id] = ["class" => $id, "alias" => $alias];
            }
        }

        foreach($serviceDefinitions as $id => $serviceDefinition){
            try {
                if($this->containerInstance->has($id)) continue;
                $alias = $serviceDefinition["alias"] ?? null;
                $service = $this->autoloader->loadClass(class: $id, alias: $alias);
                if($service === null) throw new FrameworkException("Service is null");
                $this->containerInstance->set($id, $service);
            }
            catch (\Throwable $e){
                dd($e);
            }
        }


        foreach($autoloadDirectories as $autoloadDirectory){
            try {
                $this->autoloader->loadDirectory($autoloadDirectory);
            }
            catch (\Throwable $e){
                dd($e);
            }
        }
    }

}