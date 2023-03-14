<?php

namespace Os\Framework\DependencyInjection;

use JetBrains\PhpStorm\ArrayShape;

interface ContainerRegistryInterface
{
    public static function getClasses(): array;
}