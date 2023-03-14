<?php

namespace Os\Framework\Http\Routing\Route;

use Os\Framework\Debug\Dumper;
use Os\Framework\Http\Request\Request;

class Route
{
    /** @var \Os\Framework\Http\Routing\Attribute\Route[] $routeAttributes */
    public function __construct(protected array $routeAttributes, protected \ReflectionMethod $callable){}

    public function resolve(Request $request): ?RouteCallInstruction
    {
        foreach($this->routeAttributes as $routeAttribute){
            if($routeAttribute->resolve($request) === true){
                return new RouteCallInstruction($this->callable, $routeAttribute->getParameters());
            }
        }
        return null;
    }
}