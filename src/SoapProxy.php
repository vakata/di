<?php

namespace vakata\di;

/**
 * A special class for using a DIContainer with a PHP SOAP server.
 * @codeCoverageIgnore
 */
class SoapProxy
{
    protected DIInterface $dic;
    protected object $instance;

    /**
     * Create an instance to pass to $soap->handle().
     * @param  DIInterface $dic   the DI container instance
     * @param  string      $class the class name
     */
    public function __construct(DIInterface $dic, string $class)
    {
        $this->dic = $dic;
        $this->instance = $this->dic->instance($class);
    }
    public function __call(string $method, array $args): mixed
    {
        return $this->dic->invoke($this->instance, $method, $args);
    }
}
