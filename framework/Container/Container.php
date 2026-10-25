<?php

declare(strict_types=1);

namespace Framework\Container;

final class Container
{
    private array $factories = [];
    private array $instances = [];

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new ServiceNotFoundException("Service not found: $id");
        }

        $factory = $this->factories[$id];

        $object  = $factory($this);

        $this->instances[$id] = $object;

        return $object;
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]) || array_key_exists($id, $this->instances);
    }
}
