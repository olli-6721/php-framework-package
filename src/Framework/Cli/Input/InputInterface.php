<?php

namespace Os\Framework\Cli\Input;

interface InputInterface
{
    public function getCommandName(): string;
    public function getArguments(): array;
    public function readLine(string $description = null): string;
}