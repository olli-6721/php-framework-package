<?php

namespace Os\Framework\Http\Exception;

use Throwable;

class ControllerRouteLoadingFailedException extends \Os\Framework\Exception\FrameworkException
{
    public function __construct(protected string $controllerClass, ?Throwable $previous = null)
    {
        $baseMessage = sprintf("Loading routes for controller %s failed", $this->controllerClass);
        parent::__construct(message: $baseMessage, previous: $previous);
    }
}