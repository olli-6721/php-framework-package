<?php

namespace Os\Framework\DataAbstractionLayer\Command;

use Os\Framework\Cli\Input\InputInterface;
use Os\Framework\Cli\Output\OutputInterface;
use Os\Framework\Config\ConfigReader;
use Os\Framework\Config\Exception\ConfigFileParsingException;
use Os\Framework\Config\Exception\ConfigurationViolationException;
use Os\Framework\Config\Exception\PathConfigurationViolationException;
use Os\Framework\DataAbstractionLayer\Driver\PdoDriver;
use Os\Framework\DataAbstractionLayer\Entity;
use Os\Framework\DataAbstractionLayer\EntityRepository;
use Os\Framework\DataAbstractionLayer\Migration\AbstractMigration;
use Os\Framework\DataAbstractionLayer\Service\DataType;
use Os\Framework\DependencyInjection\ContainerInterface;
use Os\Framework\DependencyInjection\Exception\ServiceNotFoundException;
use Os\Framework\Filesystem\Exception\FileNotFoundException;
use Os\Framework\Filesystem\Exception\FileReadingException;
use Os\Framework\Filesystem\Exception\NoFileExtensionFoundException;
use Os\Framework\Filesystem\FileDefinition\Php\PhpClassDefinition;
use Os\Framework\Filesystem\FileDefinition\Php\PhpFunctionDefinition;
use Os\Framework\Filesystem\FileMaker\PhpFileMaker;
use ReflectionException;

class DalMigrationsCreateCommand extends \Os\Framework\Cli\Command\AbstractCommand
{
    protected string $migrationDirectoryDestination;
    protected string $migrationNamespace;
    
    /**
     * @throws ServiceNotFoundException
     * @throws ConfigFileParsingException
     * @throws FileReadingException
     * @throws FileNotFoundException
     * @throws ConfigurationViolationException
     * @throws NoFileExtensionFoundException
     */
    public function __construct(protected ContainerInterface $container){
        /** @var ConfigReader $configReader */
        $configReader = $this->container->get(ConfigReader::class);
        if($configReader === null)
            throw new ServiceNotFoundException(ConfigReader::class);
        $entityPaths = $configReader->readPath('dal.paths.migration');
        if(count($entityPaths) < 2)
            throw new PathConfigurationViolationException("dal.paths.migration", PathConfigurationViolationException::TYPE_MIGRATION);
        $this->migrationDirectoryDestination = sprintf("%s/%s", BASE_PATH, $entityPaths[0]);
        $this->migrationNamespace = $entityPaths[1];
    }

    public static function getName(): string
    {
        return "dal:migrations:create";
    }

    /**
     * @throws ReflectionException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $migrations = $this->container->getByGroup('migration');
        $repositories = $this->container->getByGroup('repository');

        $output->startList("Creating migrations");

        /** @var EntityRepository $repository */
        foreach($repositories as $repository){
            $entityClass = $repository::getEntityClass();
            $match = array_filter($migrations, function(AbstractMigration $migration)use($entityClass){
                return $migration::getEntityClass() === $entityClass;
            });
            if(!empty($match)) continue;

            $entityReflection = (new \ReflectionClass($entityClass));
            /** @var Entity $entityInstance */
            $entityInstance = $entityReflection->newInstanceWithoutConstructor();
            $entityShortClassName = $entityReflection->getShortName();
            $migrationClassName = sprintf("%sMigration", str_replace(["Entity", "entity"], "", substr($entityShortClassName, 0, -6)));

            $output->addListItem(sprintf("Creating migration %s...", $migrationClassName), true);
            try {
                $timestamp = (new \DateTime())->getTimestamp();
                $phpClass = new PhpClassDefinition($migrationClassName, $this->migrationNamespace);
                $phpClass->setExtends(AbstractMigration::class);
                $phpClass->addDependency($entityClass);
                $phpClass->addFunction(
                    (new PhpFunctionDefinition("getTimestamp", DataType::INT, PhpClassDefinition::VISIBILITY_PUBLIC, false, true))
                        ->setContent(sprintf("return %d;", $timestamp))
                );
                $phpClass->addFunction(
                    (new PhpFunctionDefinition("execute", null, PhpClassDefinition::VISIBILITY_PUBLIC))
                        ->setContent($this->generateTableCreationFunction($entityReflection, $entityInstance::getTableName()))
                );
                $phpClass->addFunction(
                    (new PhpFunctionDefinition("destroy", null, PhpClassDefinition::VISIBILITY_PUBLIC))
                        ->setContent("")
                );
                $phpClass->addFunction(
                    (new PhpFunctionDefinition("getEntityClass", DataType::STRING, PhpClassDefinition::VISIBILITY_PUBLIC, false, true))
                        ->setContent(sprintf("return %s::class;", $entityShortClassName))
                );

                $fileMaker = new PhpFileMaker($this->migrationDirectoryDestination, $phpClass);
                $fileMaker->make();
                $output->writeLine(" success");
            }
            catch (\Throwable $e){
                $output->writeLine(sprintf(" failed (%s)", $e->getMessage()));
            }
        }
        $output->endList();

        return self::CODE_SUCCESS;
    }

    protected function generateTableCreationFunction(\ReflectionClass $entityReflection, string $tableName): string
    {
        $content = sprintf("\$this->driver->execute('CREATE TABLE IF NOT EXISTS %s (", $tableName);
        $content = sprintf("%s\n`id` %s UNIQUE NOT NULL,", $content, DataType::STRING->getSqlType());
        foreach($entityReflection->getProperties() as $property){
            switch($property->getName()){
                case "updatedAt":
                case "_uniqueIdentifier":
                case "id":
                    break;
                case "createdAt":
                    $sqlLine = sprintf("`created_at` %s NOT NULL,", DataType::DATETIME->getSqlType());
                    break;
                default:
                    $sqlLine = sprintf("`%s` %s", PdoDriver::toSnakeCase($property->getName()), DataType::createFrom($property->getType()?->getName())->getSqlType());
                    if($property->getType()?->allowsNull() !== true)
                        $sqlLine = sprintf("%s NOT NULL,", $sqlLine);
                    break;
            }
            if(isset($sqlLine))
                $content = sprintf("%s\n%s", $content, $sqlLine);

        }
        return sprintf("%s\n`updated_at` %s)');\n", $content, DataType::DATETIME->getSqlType());
    }
}