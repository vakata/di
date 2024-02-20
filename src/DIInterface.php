<?php

namespace vakata\di;

interface DIInterface
{
    public function register($class, $alias = null, array $defaults = [], $single = false);
    /**
     * @template T
     * @param class-string<T> $class
     * @param array $arguments
     * @return T
     */
    public function instance($class, array $arguments = []);
    public function invoke($class, $method, array $arguments = [], array $construct = []);
}
