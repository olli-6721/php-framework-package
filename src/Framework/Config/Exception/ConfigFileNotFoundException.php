<?php

namespace Os\Framework\Config\Exception;

use JetBrains\PhpStorm\Pure;
use Throwable;

class ConfigFileNotFoundException extends \Os\Framework\Exception\FrameworkException
{
    #[Pure]
    public function __construct(protected string $path, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("Configuration file not found, path: '%s'", $this->path), previous: $previous);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}