<?php

namespace Os\Framework\Cli\Input;

class InputData
{
    public function __construct(protected string $commandName, protected array $arguments = []){}

    /**
     * @return string
     */
    public function getCommandName(): string
    {
        return $this->commandName;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}