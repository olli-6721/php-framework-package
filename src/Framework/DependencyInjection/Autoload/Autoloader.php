<?php

namespace Os\Framework\DependencyInjection\Autoload;

use Os\Framework\Config\ConfigReader;
use Os\Framework\DataAbstractionLayer\Driver\DriverInterface;
use Os\Framework\DataAbstractionLayer\EntityRepository;
use Os\Framework\DataAbstractionLayer\Migration\AbstractMigration;
use Os\Framework\DependencyInjection\ContainerInterface;
use Os\Framework\DependencyInjection\Exception\LoadClassException;
use Os\Framework\DependencyInjection\Exception\LoadClassNameFromFileException;
use Os\Framework\DependencyInjection\Exception\LoadClassParameterException;
use Os\Framework\DependencyInjection\Instance\ContainerInstanceDefinition;
use Os\Framework\DependencyInjection\Instance\InstanceDefinition;
use Os\Framework\Debug\Dumper;
use Os\Framework\Exception\FrameworkException;
use Os\Framework\Exception\PathNotFoundException;
use Os\Framework\Cli\Command\AbstractCommand;
use Os\Framework\Logger\LoggerInterface;
use Os\Framework\Template\Render\Function\TemplateFunctionInterface;
use ReflectionException;

class Autoloader
{
    public const FORBIDDEN_PARAMETER_TYPES = ["array", "string", "bool", "int", "float"];

    protected ?ConfigReader $configReader;

    /**
     * @throws FrameworkException
     */
    public function __construct(protected ContainerInterface &$container){
        $this->configReader = $this->container->get(ConfigReader::class);
        if($this->configReader === null)
            throw new FrameworkException("ConfigReader not found in Service Container");
    }

    /**
     * @param string $directory
     * @return array
     * @description Returns array of services
     * @throws FrameworkException
     */
    public function loadDirectory(string $directory): array
    {
        $pos = strpos($directory, BASE_PATH);
        if ($pos !== false) {
            $directory = substr_replace($directory, "", $pos, strlen(BASE_PATH));
        }
        $directory = ltrim($directory, '/');
        $path = BASE_PATH."/".$directory;
        if(!is_dir($path))
            throw new PathNotFoundException($path);
        $files = $this->getPhpFiles($path);
        $subDirectories = $this->getSubDirectories($path);
        $autoloadedServices = [];
        foreach($files as $filePath){
            try {
                $className = $this->getClassNameFromFile($filePath);
                if(isset($autoloadedServices[$className])) continue;
                $instance = $this->loadClass($className);
                if($instance === null) continue;
                $autoloadedServices[$className] = $instance;
                $this->container->set($className, $instance);
                if($instance->getAlias() !== null)
                    $this->container->addSymlink($className, $instance->getAlias());
            }
            catch (\Throwable $e){
                throw new FrameworkException(message: "Error autoloading class", previous: $e);
            }
        }

        foreach($subDirectories as $subDirectory){
            try {
                $autoloadedServices = array_merge($autoloadedServices, $this->loadDirectory($subDirectory));
            }
            catch (\Throwable $e){
                throw new FrameworkException(message: "Error merging autoloaded services", previous: $e);
            }
        }
        return $autoloadedServices;
    }

    /**
     * @throws ReflectionException
     * @throws FrameworkException
     */
    public function loadClass(string|\ReflectionClass $class, string $alias = null): ?InstanceDefinition
    {
        if(!($class instanceof \ReflectionClass)){
            $className = $class;
            $class = new \ReflectionClass($class);
        }
        else {
            $className = $class->getName();
        }

        $parent = $class->getParentClass();
        $id = null;
        $groups = [];
        if($parent !== false){
            switch($parent->getName()){
                case EntityRepository::class:
                    /** @var EntityRepository $entityRepository */
                    $entityRepository = $class->newInstanceWithoutConstructor();
                    $entityClassName = $entityRepository::getEntityClass();
                    $entityName = call_user_func(sprintf("%s::getTableName", $entityClassName));
                    $id = sprintf("%s.repository", $entityName);
                    $groups[] = "repository";
                    break;
                case AbstractCommand::class:
                    if($class->getConstructor() !== null){
                        /** @var AbstractCommand $command */
                        $command = $class->newInstanceWithoutConstructor();
                    }
                    else {
                        $command = $class->newInstance();
                    }
                    $id = sprintf("%s.command", $command::getName());
                    $groups[] = "command";
                    break;
                case AbstractMigration::class:
                    $groups[] = "migration";
                    break;
            }
        }

        $interfaces = $class->getInterfaceNames();
        switch(true){
            case in_array(DriverInterface::class, $interfaces):
                $groups[] = "db_driver";
                break;
            case in_array(TemplateFunctionInterface::class, $interfaces):
                $groups[] = "template_function";
                break;
            case in_array(LoggerInterface::class, $interfaces):
                $groups[] = "logger";
                if($class->getConstructor() !== null){
                    /** @var LoggerInterface $command */
                    $logger = $class->newInstanceWithoutConstructor();
                }
                else {
                    $logger = $class->newInstance();
                }
                $id = sprintf("%s.logger", $logger::getName());
                break;
        }

        $autoloadedParameters = $this->loadServiceConfig($className);
        if($class->isInterface() && !empty($autoloadedParameters["class"])){
            return self::loadClass($autoloadedParameters["class"], $className);
        }

        if($alias !== null){
            $id = $alias;
        }

        $constructorMethod = $class->getConstructor();
        if($constructorMethod === null)
            return new InstanceDefinition($className, [], $id, $groups);
        if(!$constructorMethod->isPublic())
            throw new FrameworkException(sprintf("Cant autowire class '%s' because constructor is not public", $className));
        $parameterDefinitions = $constructorMethod->getParameters();

        $parameters = [];

        foreach($parameterDefinitions as $parameterDefinition){
            try {
                $this->loadParameterDefinition($parameters, $parameterDefinition, $class);
            }
            catch (\Throwable $e){
                throw new LoadClassParameterException($className, $parameterDefinition, $e);
            }
        }
        return new InstanceDefinition($className, $parameters, $id, $groups);
    }

    /**
     * @throws ReflectionException
     * @throws FrameworkException
     */
    protected function loadParameterDefinition(array &$parameters, \ReflectionParameter $parameterDefinition, \ReflectionClass $class){
        $parameterType = $parameterDefinition->getType()->getName();

        if(in_array($parameterType, self::FORBIDDEN_PARAMETER_TYPES)){
            $parameters[$parameterDefinition->getPosition()] = $parameterDefinition->getDefaultValue();
            return;
        }
        $parameterClassName = $parameterDefinition->getType()->getName();
        if($parameterClassName === ContainerInterface::class || isset(class_implements($parameterClassName)[ContainerInterface::class])){
            $parameters[$parameterDefinition->getPosition()] = new ContainerInstanceDefinition(ContainerInterface::class, []);
            return;
        }
        $isInterface = $parameterDefinition?->getDeclaringClass()?->isInterface() ?? false;
        $parameterInstance = null;
        if($isInterface){
            /** @var ConfigReader $configReader */
            $configReader = $this->container->get(ConfigReader::class);
            if($configReader === null) return;
            $interfaceConfig = $configReader->read("di", ["interfaces"]);
            if(isset($interfaceConfig["interfaces"][$parameterDefinition?->getType()->getName()]))
                $parameterInstance = self::loadClass($interfaceConfig["interfaces"][$parameterDefinition?->getType()->getName()]);
            else
                return;
        }
        if($parameterInstance === null){
            if(!$this->container->has($parameterClassName)){
                $parameterInstance = self::loadClass($parameterClassName);
                if($parameterInstance !== null && $class->getConstructor() !== null)
                    $this->container->set($parameterClassName, $parameterInstance);
                else
                    $parameterInstance = new InstanceDefinition($parameterClassName, []);
            }
            else {
                $definition = $this->container->getDefinition($parameterClassName);
                $parameterInstance = $definition === null ? null : $definition["definition"];
            }
        }
        if($parameterInstance === null)
            throw new FrameworkException(sprintf("Could not get service '%s'", $parameterClassName));

        $parameters[$parameterDefinition->getPosition()] = $parameterInstance;
    }

    protected function getPhpFiles(string $path): array
    {
        if(!str_ends_with($path, "/"))
            $path = sprintf("%s/", $path);
        return glob(sprintf("%s*.{php}", $path), GLOB_BRACE);
    }

    protected function getSubDirectories(string $path): array
    {
        if(!str_ends_with($path, "/"))
            $path = sprintf("%s/", $path);
        return array_filter(glob(sprintf("%s*", $path)), 'is_dir');
    }

    /**
     * @throws \Exception
     */
    protected function getClassNameFromFile(string $file): string
    {
        $getNext=null;
        $getNextNamespace=false;
        $skipNext=false;
        $isAbstract = false;
        $rs = ['namespace'=>null, 'class'=>null, 'trait'=>null, 'interface'=>null, 'abstract'=>null];
        foreach(\PhpToken::tokenize(file_get_contents($file)) as $token){
            if($token->isIgnorable()) continue;
            $name = $token->getTokenName();
            switch($name){
                case 'T_NAMESPACE':
                    $getNextNamespace=true;
                    break;
                case 'T_EXTENDS':
                case 'T_USE':
                case 'T_IMPLEMENTS':
                    $skipNext = true;
                    break;
                case 'T_ABSTRACT':
                    $isAbstract = true;
                    break;
                case 'T_CLASS':
                    if($skipNext) {
                        $skipNext=false;
                    }
                    else {
                        $getNext = strtolower(substr($name, 2));
                    }
                    break;
                case 'T_NAME_QUALIFIED':
                case 'T_STRING':
                    if($getNextNamespace) {
                        if(array_filter($rs)) {
                            throw new LoadClassNameFromFileException($file, $rs['class'] ?? null, $rs['namespace'] ?? null, new FrameworkException(sprintf('Namespace mus be defined first in %s', $file)));
                        }
                        else {
                            $rs['namespace'] = $token->text;
                        }
                        $getNextNamespace=false;
                    }
                    elseif($skipNext) {
                        $skipNext=false;
                    }
                    elseif($getNext) {
                        if($isAbstract) {
                            $isAbstract=false;
                            $getNext = 'abstract';
                        }
                        if($getNext === 'class')
                            $rs[$getNext]=$token->text;
                        $getNext=null;
                    }
                    break;
                default:
                    $getNext=null;
            }
        }
        if(empty($rs["class"]) || empty($rs["namespace"]))
            throw new LoadClassNameFromFileException($file, $rs['class'] ?? null, $rs['namespace'] ?? null);
        return sprintf("%s\\%s", $rs["namespace"], $rs["class"]);
    }

    protected function loadServiceConfig(string $serviceName): ?array
    {
        $config = $this->configReader->read("di", ["services"]);
        return $config["services"][$serviceName] ?? null;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
}