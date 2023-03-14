<?php

namespace Os\Framework\DataAbstractionLayer\Command;

use Os\Framework\Cli\Input\InputInterface;
use Os\Framework\Cli\Output\OutputInterface;
use Os\Framework\Config\ConfigReader;
use Os\Framework\Config\Exception\ConfigFileParsingException;
use Os\Framework\Config\Exception\ConfigurationViolationException;
use Os\Framework\Config\Exception\PathConfigurationViolationException;
use Os\Framework\DataAbstractionLayer\Attribute\Column;
use Os\Framework\DataAbstractionLayer\Entity;
use Os\Framework\DataAbstractionLayer\EntityCollection;
use Os\Framework\DataAbstractionLayer\EntityRepository;
use Os\Framework\DataAbstractionLayer\Service\DataType;
use Os\Framework\DependencyInjection\ContainerInterface;
use Os\Framework\DependencyInjection\Exception\ServiceNotFoundException;
use Os\Framework\Filesystem\Exception\FileNotFoundException;
use Os\Framework\Filesystem\Exception\FileReadingException;
use Os\Framework\Filesystem\Exception\NoFileExtensionFoundException;
use Os\Framework\Filesystem\FileDefinition\Php\PhpClassDefinition;
use Os\Framework\Filesystem\FileDefinition\Php\PhpFunctionArgumentDefinition;
use Os\Framework\Filesystem\FileDefinition\Php\PhpFunctionDefinition;
use Os\Framework\Filesystem\FileDefinition\Php\PhpPropertyDefinition;
use Os\Framework\Filesystem\FileMaker\PhpFileMaker;

class DalEntityCreateCommand extends \Os\Framework\Cli\Command\AbstractCommand
{
    protected string $entityDirectoryDestination;
    protected string $entityNamespace;

    protected string $repositoryDirectoryDestination;
    protected string $repositoryNamespace;

    /**
     * @throws ServiceNotFoundException
     * @throws ConfigFileParsingException
     * @throws FileNotFoundException
     * @throws NoFileExtensionFoundException
     * @throws FileReadingException
     * @throws ConfigurationViolationException
     */
    public function __construct(protected ContainerInterface $container)
    {
        /** @var ConfigReader $configReader */
        $configReader = $this->container->get(ConfigReader::class);
        if($configReader === null)
            throw new ServiceNotFoundException(ConfigReader::class);
        $entityPaths = $configReader->readPath('dal.paths.entity');
        if(count($entityPaths) < 2)
            throw new PathConfigurationViolationException("dal.paths.entity", PathConfigurationViolationException::TYPE_ENTITY);
        $this->entityDirectoryDestination = sprintf("%s/%s", BASE_PATH, $entityPaths[0]);
        $this->entityNamespace = $entityPaths[1];
        
        $repositoryPaths = $configReader->readPath('dal.paths.repository');
        if(count($repositoryPaths) < 2)
            throw new PathConfigurationViolationException("dal.paths.repository", PathConfigurationViolationException::TYPE_REPOSITORY);
        $this->repositoryDirectoryDestination = sprintf("%s/%s", BASE_PATH, $repositoryPaths[0]);
        $this->repositoryNamespace = $repositoryPaths[1];
    }

    public static function getName(): string
    {
        return "dal:entity:create";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityName = ucfirst($input->readLine("Create new entity, name (CamelCase): "));
        if(str_ends_with($entityName,  "Entity"))
            $entityName = substr($entityName, 0, -6);

        $entityClassName = sprintf("%sEntity", $entityName);
        $repositoryClassName = sprintf("%sRepository", $entityName);
        $collectionClassName = sprintf("%sEntityCollection", $entityName);

        $fields = [];

        while(true){
            $output->writeLine("");
            $fieldName = lcfirst($input->readLine("Add new field, name (camelCase, type enter to stop adding fields): "));
            if(empty($fieldName)){
                break;
            }
            $dataType = $this->displayFieldTypeSelection($input, $output);
            $fields[$fieldName] = $dataType;
        }

        $this->generateEntityClassFile($entityClassName, $repositoryClassName, $fields);
        $this->generateRepositoryClassFile($entityClassName, $repositoryClassName, $collectionClassName);
        $this->generateCollectionClassFile($entityClassName, $collectionClassName);

        return self::CODE_SUCCESS;
    }

    protected function displayFieldTypeSelection(InputInterface $input, OutputInterface $output): DataType
    {
        $output->writeLine("");
        $output->startList("Available Types");
        foreach(DataType::cases() as $type){
            $output->addListItem($type->name);
        }
        $output->endList();
        $fieldType = $input->readLine("Choose field-type (see above for available field-types): ");
        $dataType = DataType::createFrom($fieldType);
        if($dataType === null || empty($fieldType)){
            $output->writeError(sprintf("'%s' is not a valid field-type", $fieldType));
            return $this->displayFieldTypeSelection($input, $output);
        }
        return $dataType;
    }

    protected function generateEntityClassFile(string $entityClassName, string $repositoryClassName, array $fields){
        $phpClass = new PhpClassDefinition($entityClassName, $this->entityNamespace);
        $phpClass->setExtends(Entity::class);
        $phpClass->addDependency(Column::class);
        $phpClass->addDependency(DataType::class);
        $phpClass->addDependency(sprintf("%s\\%s", $this->repositoryNamespace, $repositoryClassName));


        /**
         * @var string $fieldName
         * @var DataType $fieldType
         */
        foreach($fields as $fieldName => $fieldType){
            $phpClass->addProperty(
                (new PhpPropertyDefinition($fieldName, $fieldType, PhpClassDefinition::VISIBILITY_PROTECTED))
                    ->addAttribute("Column", [sprintf("\%s::%s", $fieldType::class, $fieldType->name)])
            );
        }

        $phpClass->addFunction(
            (new PhpFunctionDefinition("getRepositoryClass", DataType::STRING, PhpClassDefinition::VISIBILITY_PUBLIC, false, true))
                ->setContent(sprintf("return %s::class;", $repositoryClassName))
        );

        /**
         * @var string $fieldName
         * @var DataType $fieldType
         */
        foreach($fields as $fieldName => $fieldType){
            $phpClass->addFunction(
                (new PhpFunctionDefinition(sprintf("get%s", ucfirst($fieldName)), $fieldType,PhpClassDefinition::VISIBILITY_PUBLIC))
                    ->setContent(sprintf("return \$this->%s;", $fieldName))
            );
            $phpClass->addFunction(
                (new PhpFunctionDefinition(sprintf("set%s", ucfirst($fieldName)), null,PhpClassDefinition::VISIBILITY_PUBLIC))
                    ->setContent(sprintf("\$this->%s = \$%s;", $fieldName, $fieldName))
                    ->addArgument(new PhpFunctionArgumentDefinition($fieldName, $fieldType))
            );
        }

        $fileMaker = new PhpFileMaker($this->entityDirectoryDestination, $phpClass);
        $fileMaker->make();
    }
    protected function generateRepositoryClassFile(string $entityClassName, string $repositoryClassName, string $collectionClassName){
        $phpClass = new PhpClassDefinition($repositoryClassName, $this->repositoryNamespace);
        $phpClass->setExtends(EntityRepository::class);
        $phpClass->addDependency(sprintf("%s\\%s", $this->entityNamespace, $entityClassName));
        $phpClass->addDependency(sprintf("%s\\%s", $this->entityNamespace, $collectionClassName));
        $phpClass->setAnnotations(str_replace(":Entity:", $entityClassName,"/**\n* @method :Entity:|null update(:Entity: \$entity)\n* @method :Entity:|null create(:Entity: \$entity)\n* @method :Entity:|null find(string \$id)\n* @method :Entity:|null findOneBy(array \$criteria)\n* @method :Entity:Collection|null findBy(array \$criteria)\n*/"));

        $phpClass->addFunction(
            (new PhpFunctionDefinition("getEntityClass", DataType::STRING, PhpClassDefinition::VISIBILITY_PUBLIC, false, true))
                ->setContent(sprintf("return %s::class;", $entityClassName))
        );

        $phpClass->addFunction(
            (new PhpFunctionDefinition("getEntityCollectionClass", DataType::STRING, PhpClassDefinition::VISIBILITY_PUBLIC, false, true))
                ->setContent(sprintf("return %s::class;", $collectionClassName))
        );

        $fileMaker = new PhpFileMaker($this->repositoryDirectoryDestination, $phpClass);
        $fileMaker->make();
    }

    protected function generateCollectionClassFile(string $entityClassName, string $collectionClassName){
        $phpClass = new PhpClassDefinition($collectionClassName, $this->entityNamespace);
        $phpClass->setExtends(EntityCollection::class);
        $phpClass->addDependency(sprintf("%s\\%s", $this->entityNamespace, $entityClassName));
        $phpClass->setAnnotations(str_replace(":Entity:", $entityClassName, "/**\n* @method :Entity:[] getEntities()\n* @method add(:Entity: \$entity)\n* @method :Entity: get(string \$id)\n* @method :Entity: first()\n* @method __construct(:Entity:[] \$entities = [])\n*/"));

        $fileMaker = new PhpFileMaker($this->entityDirectoryDestination, $phpClass);
        $fileMaker->make();
    }
}