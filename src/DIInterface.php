<?php

namespace vakata\di;

interface DIInterface
{
    /**
     * Get a previously registered instance.
     * 
     * @param string $clss
     * @return object
     */
    public function get(string $clss): object;

    /**
     * Is there an instance registered under the sprcified name or alias
     * 
     * @param string $clss
     * @return bool
     */
    public function has(string $clss): bool;
    
    /**
     * @param object $instance the instance to register
     * @param array $alias optional array of aliases
     * @param bool $interfaces should all implemented interfaces be registered as aliases
     * @param bool $parents should all ancestor classes be registered as aliases
     * @return $this
     */
    public function register(object $instance, bool $alias = true): static;

    /**
     * @param string $clss
     * @param array $defaults
     * @return static
     */
    public function defaults(string $clss, array $defaults): static;

    /**
     * @param string $clss
     * @param array<string> $aliases
     * @param bool $interfaces
     * @param bool $parents
     * @return static
     */
    public function alias(string $clss, array $aliases = [], bool $interfaces = true, bool $parents = false): static;

    /**
     * Create an instance of a class
     * 
     * @template T
     * @param class-string<T> $class
     * @param array $arguments
     * @param bool $force
     * @return T
     */
    public function instance(string $class, array $arguments = [], bool $force = false): object;
    
    /**
     * Invoke a method
     * 
     * @param string $clss A classname
     * @param string $method the method name
     * @param array $arguments arguments to pass when invoking
     * @param array $construct arguments to pass when creating an instance
     * @return mixed
     */
    public function invoke(string $clss, string $method, array $arguments = [], array $construct = []): mixed;
}
