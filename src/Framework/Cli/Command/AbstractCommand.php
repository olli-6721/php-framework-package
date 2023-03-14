<?php

namespace Os\Framework\Cli\Command;

use Os\Framework\Cli\Input\InputInterface;
use Os\Framework\Cli\Output\OutputInterface;

abstract class AbstractCommand
{
    public const CODE_SUCCESS = 0;
    public const CODE_ERROR = 1;

    abstract public static function getName(): string;

    abstract public function execute(InputInterface $input, OutputInterface $output): int;
}