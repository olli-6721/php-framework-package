<?php

namespace Os\Framework\Config\Exception;

use Throwable;

class PathConfigurationViolationException extends ConfigurationViolationException
{
    public const TYPE_CONTROLLER = "Controller";
    public const TYPE_ENTITY = "Entity";
    public const TYPE_MIGRATION = "Migration";
    public const TYPE_REPOSITORY = "Repository";

    public function __construct(string $path, string $type, ?Throwable $previous = null)
    {
        parent::__construct(path: $path, violation: sprintf("%s path-data must contain an array with two indices", $type), previous: $previous);
    }
}