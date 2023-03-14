<?php

namespace Os\Framework\DependencyInjection\Exception;

use Os\Framework\Exception\FrameworkException;
use Throwable;

class LoadClassParameterException extends FrameworkException
{
    public function __construct(protected string $className, protected \ReflectionParameter|string|null $parameter = null, ?Throwable $previous = null)
    {
        $parameterName = $this->parameter;
        try {
            if($this->parameter instanceof \ReflectionParameter)
                $parameterName = $this->parameter->getName();
            if($this->parameter === null)
                $parameterName = "(Not given)";
        }
        catch (Throwable $e){
            $parameterName = "(Exception thrown when tried to access parameter name)";
        }

        parent::__construct(message: sprintf("Error loading class '%s' because parameter '%s' cannot be loaded", $className, $parameterName), previous: $previous);
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return \ReflectionParameter|string|null
     */
    public function getParameter(): \ReflectionParameter|string|null
    {
        return $this->parameter;
    }
}