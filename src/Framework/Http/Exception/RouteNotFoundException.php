<?php

namespace Os\Framework\Http\Exception;

use JetBrains\PhpStorm\Pure;
use Throwable;

class RouteNotFoundException extends \Os\Framework\Exception\FrameworkException
{
    #[Pure]
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(message: "Route not found", previous: $previous);
    }
}