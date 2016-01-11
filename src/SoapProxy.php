<?php

namespace vakata\di;

class SoapProxy
{
    protected $dic;
    protected $instance;

    public function __construct(DI $dic, $class)
    {
        $this->dic = $dic;
        $this->instance = $this->di->instance($class);
    }
    public function __call($method, $args)
    {
        return $this->dic->invoke($this->instance, $method, $args);
    }
}
