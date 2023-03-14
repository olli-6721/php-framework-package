<?php

namespace Os\Framework\Cli\Exception;

use Throwable;

class CommandNotFoundException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(string $commandName, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("Command '%s' not found", $commandName), previous: $previous);
    }
}