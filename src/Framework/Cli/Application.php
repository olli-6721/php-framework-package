<?php

namespace Os\Framework\Cli;

use JetBrains\PhpStorm\Pure;
use Os\Framework\Cli\Output\OutputHandler;
use Os\Framework\Cli\Output\OutputInterface;
use Os\Framework\Debug\Dumper;
use Os\Framework\DependencyInjection\ContainerInterface;
use Os\Framework\Cli\Command\AbstractCommand;
use Os\Framework\Cli\Exception\CommandNotFoundException;
use Os\Framework\Cli\Input\InputHandler;
use Os\Framework\Cli\Input\InputInterface;
use Os\Framework\Exception\FrameworkException;
use Throwable;

class Application
{
    protected InputInterface $input;
    protected OutputInterface $output;

    public function __construct(protected ContainerInterface $container)
    {
        $this->input = new InputHandler();
        $this->output = new OutputHandler();
    }

    /**
     * @throws CommandNotFoundException
     * @throws FrameworkException|Throwable
     */
    public function run(){
        /** @var AbstractCommand|null $command */
        $command = $this->container->get(sprintf("%s.command", $this->input->getCommandName()));
        if($command === null){
            $availableCommands = $this->container->getByGroup('command');

            $this->output->startList("Available Commands");
            /** @var AbstractCommand $command */
            foreach($availableCommands as $command){
                $this->output->addListItem($command::getName());
            }
            $this->output->endList();
            $this->output->writeLine("");
            $this->output->writeError(sprintf("Command %s not found", $this->input->getCommandName()));
            return;
        }
        try {
            $code = $command->execute($this->input, $this->output);
        }
        catch (Throwable $e){
            throw $e;
        }
    }
}