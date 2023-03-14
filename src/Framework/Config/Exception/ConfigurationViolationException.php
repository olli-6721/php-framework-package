<?php

namespace Os\Framework\Config\Exception;

use JetBrains\PhpStorm\Pure;
use Throwable;

class ConfigurationViolationException extends \Os\Framework\Exception\FrameworkException
{
    #[Pure]
    public function __construct(protected string $path, protected string $violation, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("Configuration violation: '%s', violation: '%s'", $this->path, $this->violation), previous: $previous);
    }
}