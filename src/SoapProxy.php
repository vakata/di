<?php

namespace vakata\di;

/**
 * A special class for using a DIContainer with a PHP SOAP server.
 * @codeCoverageIgnore
 */
class SoapProxy
{
    protected $dic;
    protected $instance;

    /**
     * Create an instance to pass to $soap->handle().
     * @method __construct
     * @param  DIInterface $dic   the DI container instance
     * @param  string      $class the class name
     */
    public function __construct(DIInterface $dic, $class)
    {
        $this->dic = $dic;
        $this->instance = $this->di->instance($class);
    }
    public function __call($method, $args)
    {
        return $this->dic->invoke($this->instance, $method, $args);
    }
}
