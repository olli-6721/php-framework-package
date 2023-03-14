<?php

namespace Os\Framework\DataAbstractionLayer\Command;

use Os\Framework\Cli\Input\InputInterface;
use Os\Framework\Cli\Output\OutputInterface;
use Os\Framework\Config\ConfigReader;
use Os\Framework\DataAbstractionLayer\Driver\DriverInterface;
use Os\Framework\DataAbstractionLayer\Entity\MigrationVersionEntity;
use Os\Framework\DataAbstractionLayer\Entity\MigrationVersionRepository;
use Os\Framework\DataAbstractionLayer\Migration\AbstractMigration;
use Os\Framework\DataAbstractionLayer\Migration\MigrationVersionMigration;
use Os\Framework\Debug\Dumper;
use Os\Framework\DependencyInjection\Autoload\Autoloader;
use Os\Framework\DependencyInjection\Container;
use Os\Framework\DependencyInjection\ContainerInterface;
use Os\Framework\DependencyInjection\Exception\ServiceNotFoundException;
use Os\Framework\Exception\FrameworkException;

class DalMigrationsMigrateCommand extends \Os\Framework\Cli\Command\AbstractCommand
{

    public function __construct(protected ContainerInterface $container){}

    public static function getName(): string
    {
        return "dal:migrations:migrate";
    }

    /**
     * @throws FrameworkException
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var DriverInterface|null $driver */
        $driver = $this->container->get(DriverInterface::class);
        if($driver === null)
            throw new ServiceNotFoundException(DriverInterface::class);

        /** @var ConfigReader|null $configReader */
        $configReader = $this->container->get(ConfigReader::class);
        if($configReader === null)
            throw new ServiceNotFoundException(ConfigReader::class);

        /** @var MigrationVersionRepository|null $migrationVersionRepository */
        $migrationVersionRepository = $this->container->get(MigrationVersionRepository::class);
        if($migrationVersionRepository === null)
            throw new ServiceNotFoundException(MigrationVersionRepository::class);


        $migrationTableMigration = new MigrationVersionMigration($driver, $configReader);
        $migrationTableMigration->execute();

        $migrations = $this->container->getByGroup("migration");
        /** @var AbstractMigration $migration */
        foreach($migrations as $migration){
            if($migration::class === MigrationVersionMigration::class) continue;

            /** @var MigrationVersionEntity|null $migrationVersion */
            $migrationVersion = $migrationVersionRepository->findOneBy(["class" => $migration::class]);
            if($migrationVersion !== null && $migrationVersion->getVersionTimestamp() >= $migration::getTimestamp()){
                continue;
            }

            $output->write(sprintf("Executing migration %s...", $migration::class));
            try {
                $migration->execute();

                if($migrationVersion === null) {
                    $migrationVersion = (new MigrationVersionEntity())->setClass($migration::class)->setVersionTimestamp($migration::getTimestamp());
                    $migrationVersionRepository->create($migrationVersion);
                }
                else {
                    $migrationVersion->setVersionTimestamp($migration::getTimestamp());
                    $migrationVersionRepository->update($migrationVersion);
                }
                $output->writeLine(" success");
            }
            catch (\Throwable $e){
                $output->writeLine(" failed");
                throw new FrameworkException("Migration failed", 400, $e);
            }
        }
        return self::CODE_SUCCESS;
    }
}