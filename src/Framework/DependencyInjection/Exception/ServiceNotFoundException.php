<?php

namespace Os\Framework\DependencyInjection\Exception;

use JetBrains\PhpStorm\Pure;
use Throwable;

class ServiceNotFoundException extends \Os\Framework\Exception\FrameworkException
{
    #[Pure]
    public function __construct(protected string $serviceId, ?Throwable $previous = null)
    {
        parent::__construct(message: sprintf("Service '%s' not found in service container", $this->serviceId), previous: $previous);
    }
}