<?php

namespace vakata\di;

interface DIInterface
{
    public function get(string $id): mixed;
    public function has(string $id): bool;
    public function register(mixed $class, mixed $alias = null, array $defaults = [], bool $single = false): mixed;
    public function instance(string $class, array $arguments = [], bool $onlyExisting = false): mixed;
    public function invoke(mixed $class, string $method, array $arguments = [], array $construct = []): mixed;
}
