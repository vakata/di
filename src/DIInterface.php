<?php

namespace vakata\di;

interface DIInterface
{
    public function register($alias, $class, array $defaults = [], $single = false);
    public function instance($class, array $arguments = []);
    public function decorate($expression, callable $callback, $mode = 'after');
    public function invoke($class, $method, array $arguments = [], array $construct = []);
}
