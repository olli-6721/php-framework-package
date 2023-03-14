<?php

namespace Os\Framework\Http\Command;

use Os\Framework\Cli\Input\InputInterface;
use Os\Framework\Cli\Output\OutputInterface;
use Os\Framework\Config\ConfigReader;
use Os\Framework\Config\Exception\ConfigFileNotFoundException;
use Os\Framework\Config\Exception\ConfigFileParsingException;
use Os\Framework\Config\Exception\ConfigurationViolationException;
use Os\Framework\Config\Exception\EnvFileNotFoundException;
use Os\Framework\Config\Exception\PathConfigurationViolationException;
use Os\Framework\DataAbstractionLayer\Driver\PdoDriver;
use Os\Framework\DataAbstractionLayer\Service\DataType;
use Os\Framework\Debug\Dumper;
use Os\Framework\DependencyInjection\ContainerInterface;
use Os\Framework\DependencyInjection\Exception\ServiceNotFoundException;
use Os\Framework\Filesystem\Exception\CannotCreateDirectoryException;
use Os\Framework\Filesystem\Exception\FileNotFoundException;
use Os\Framework\Filesystem\Exception\FileReadingException;
use Os\Framework\Filesystem\Exception\NoFileExtensionFoundException;
use Os\Framework\Filesystem\FileDefinition\Php\PhpClassDefinition;
use Os\Framework\Filesystem\FileDefinition\Php\PhpFunctionDefinition;
use Os\Framework\Filesystem\FileMaker\PhpFileMaker;
use Os\Framework\Filesystem\Filesystem;
use Os\Framework\Http\Controller\AbstractController;
use Os\Framework\Http\Routing\Attribute\Route;

class HttpControllerCreateCommand extends \Os\Framework\Cli\Command\AbstractCommand
{
    protected string $controllerDirectoryDestination;
    protected string $controllerNamespace;

    protected Filesystem $filesystem;

    /**
     * @throws ServiceNotFoundException
     * @throws ConfigFileParsingException
     * @throws FileNotFoundException
     * @throws NoFileExtensionFoundException
     * @throws FileReadingException
     * @throws ConfigurationViolationException
     * @throws ConfigFileNotFoundException
     * @throws EnvFileNotFoundException
     */
    public function __construct(protected ContainerInterface $container)
    {
        /** @var ConfigReader $configReader */
        $configReader = $this->container->get(ConfigReader::class);
        if($configReader === null)
            throw new ServiceNotFoundException(ConfigReader::class);
        $controllerPaths = $configReader->readPath('http.paths.controller');
        if(count($controllerPaths) < 2)
            throw new PathConfigurationViolationException("http.paths.controller", PathConfigurationViolationException::TYPE_CONTROLLER);
        $this->controllerDirectoryDestination = sprintf("%s/%s", BASE_PATH, $controllerPaths[0]);
        $this->controllerNamespace = $controllerPaths[1];
        $this->filesystem = new Filesystem();
    }
    
    public static function getName(): string
    {
        return "http:controller:create";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $controllerName = ucfirst($input->readLine("Create new controller, name (CamelCase): "));
        if(str_ends_with($controllerName,  "Controller"))
            $controllerName = substr($controllerName, 0, -10);

        $controllerClassName = sprintf("%sController", $controllerName);
        $controllerTemplateDirectoryName = PdoDriver::toSnakeCase($controllerName);
        $controllerTemplateDirectoryPath = sprintf("%s%stemplates%s%s", BASE_PATH, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $controllerTemplateDirectoryName);
        $controllerTemplatePath = sprintf("%s%sindex.template.html", $controllerTemplateDirectoryPath, DIRECTORY_SEPARATOR);
        if(!$this->filesystem->directoryExists($controllerTemplateDirectoryPath)){
            $state = $this->filesystem->createDirectory($controllerTemplateDirectoryPath);
            if($state === false)
                throw new CannotCreateDirectoryException($controllerTemplateDirectoryPath);
        }
        if(!$this->filesystem->fileExists($controllerTemplatePath)){
            $state = $this->filesystem->touch($controllerTemplatePath);
            if($state === false)
                throw new CannotCreateDirectoryException($controllerTemplatePath);
            $this->filesystem->write($controllerTemplatePath, $this->generateControllerIndexTemplateContent());
        }

        $phpClass = new PhpClassDefinition($controllerClassName, $this->controllerNamespace);
        $phpClass->setExtends(AbstractController::class);
        $phpClass->addDependency(Route::class);
        $phpClass->addFunction(
            (new PhpFunctionDefinition("index"))
                ->setContent($this->generateControllerIndexFunctionContent($controllerClassName, $controllerTemplateDirectoryName))
                ->addAttribute("Route", [sprintf('"/%s/index"', strtolower($controllerName)), 'name: "index"', 'methods: ["GET"], ignoreQuery: true'])
        );

        $fileMaker = new PhpFileMaker($this->controllerDirectoryDestination, $phpClass);
        $fileMaker->make();

        return self::CODE_SUCCESS;
    }

    protected function generateControllerIndexFunctionContent(string $controllerName, string $controllerTemplateDirectoryName): string
    {
        return sprintf("return \$this->render('%s%sindex.template.html', ['message' => 'Hello %s controller']);", $controllerTemplateDirectoryName, DIRECTORY_SEPARATOR, $controllerName);
    }

    protected function generateControllerIndexTemplateContent(): string
    {
        return '{% extends "base.template.html"%}

{% block content %}
    <h1>{{ message }}</h1>
{% endblock %}';
    }
}