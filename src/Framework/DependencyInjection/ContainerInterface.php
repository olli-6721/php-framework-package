<?php

namespace Os\Framework\DependencyInjection;

use Os\Framework\DependencyInjection\Instance\InstanceDefinition;

interface ContainerInterface
{
    public function set(string $id, InstanceDefinition $service): static;

    public function get(string $id);

    public function getByGroup(string $groupName): ?array;

    public function getDefinition(string $id);

    public function has(string $id): bool;

    public function destroy();

    public function isInitialized(string $id): ?bool;

    public static function build(array $services = []): static;

    public function addSymlink(string $existingId, string $symlinkId): static;

    public function callMethod(object|string $class, string|\ReflectionMethod $method, array $parameters = []);
}