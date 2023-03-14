<?php

namespace Os\Framework\Http\Routing\Route;

class RouteCallInstruction
{
    public function __construct(protected \ReflectionMethod $method, protected array $parameters){}

    /**
     * @return \ReflectionMethod
     */
    public function getMethod(): \ReflectionMethod
    {
        return $this->method;
    }

    /**
     * @param \ReflectionMethod $method
     */
    public function setMethod(\ReflectionMethod $method): void
    {
        $this->method = $method;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }
}