<?php

namespace Os\Framework\Exception;

use JetBrains\PhpStorm\Pure;
use Throwable;

class MethodNotFoundException extends FrameworkException
{
    #[Pure]
    public function __construct(protected string $methodName, protected ?string $className, ?Throwable $previous = null)
    {
        $baseMessage = sprintf("Method '%s' not found", $this->methodName);
        if($this->className !== null)
            $baseMessage = sprintf("%s in class '%s'", $baseMessage, $this->className);
        parent::__construct(message: $baseMessage, previous: $previous);
    }
}