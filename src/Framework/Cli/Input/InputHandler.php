<?php

namespace Os\Framework\Cli\Input;

use JetBrains\PhpStorm\Pure;
use Os\Framework\Cli\Input\Argument\ArgumentHandler;

class InputHandler implements InputInterface
{
    protected InputData $data;

    public function __construct()
    {
        $this->data = (new ArgumentHandler())->parse();
    }

    #[Pure]
    public function getArguments(): array
    {
        return $this->data->getArguments();
    }

    #[Pure]
    public function getCommandName(): string
    {
        return $this->data->getCommandName();
    }

    public function readLine(string $description = null): string
    {
        return readline($description);
    }
}