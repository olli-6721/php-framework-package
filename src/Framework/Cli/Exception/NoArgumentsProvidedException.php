<?php

namespace Os\Framework\Cli\Exception;

use Throwable;

class NoArgumentsProvidedException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct("No arguments provided, command can therefore not be found");
    }
}