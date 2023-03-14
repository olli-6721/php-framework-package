<?php

namespace Os\Framework\Filesystem\Command;

use Os\Framework\Cli\Input\InputInterface;
use Os\Framework\Cli\Output\OutputInterface;
use Os\Framework\DependencyInjection\ContainerInterface;
use Os\Framework\Filesystem\Cache\FileBasedCacheProvider;

class FilesystemCacheClearCommand extends \Os\Framework\Cli\Command\AbstractCommand
{

    public static function getName(): string
    {
        return "cache:clear";
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->write("Clearing cache...");
        try{
            FileBasedCacheProvider::clear();
            $output->writeLine(" DONE");
            return self::CODE_SUCCESS;
        }
        catch (\Throwable $e){
            $output->writeLine(" FAILED");
            $output->writeError("Failed to clear apcu cache");
            return self::CODE_ERROR;
        }
    }
}