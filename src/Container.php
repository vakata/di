<?php

namespace vakata\di;

class Container implements DIInterface
{
    protected $replacements = [];
    protected $instances = [];
    protected $decorations = [];

    protected function arguments(\ReflectionMethod $method, array $args = [])
    {
        $arguments = [];
        foreach ($method->getParameters() as $k => $v) {
            // named first
            if ($v->getName() && isset($args[$v->getName()])) {
                $arguments[] = $args[$v->getName()];
                unset($args[$v->getName()]);
                continue;
            }
            // then with type hints (provided the next argument matches)
            if ($v->getClass()) {
                $name = $v->getClass()->name;
                if (count($args) && $args[0] instanceof $name) {
                    $arguments[] = array_shift($args);
                    continue;
                }
                $temp = $this->instance('\\'.$name);
                if ($temp !== null) {
                    $arguments[] = $temp;
                    continue;
                }
                $arguments[] = $v->isOptional() ? $v->getDefaultValue() : null;
                continue;
            }
            // otherwise - just append
            if (count($args)) {
                $arguments[] = array_shift($args);
                continue;
            }
            // or get the default value or null if nothing is left in the $args array
            $arguments[] = $v->isOptional() ? $v->getDefaultValue() : null;
        }

        return $arguments;
    }

    public function register($alias, $class, array $defaults = [], $single = false)
    {
        if (!is_array($alias)) {
            $alias = [$alias];
        }
        if (is_object($class)) {
            $single = true;
            $temp = '\\'.get_class($class);
            $this->instances[$temp] = $class;
            $class = $temp;
        }
        foreach ($alias as $name) {
            $this->replacements[strtolower($name)] = [$class, $defaults, $single];
        }
    }

    public function instance($class, array $arguments = [])
    {
        $defaults = [];
        if (isset($this->replacements[strtolower($class)])) {
            list($class, $defaults, $single) = $this->replacements[strtolower($class)];
            $prepend = [];
            foreach ($arguments as $k => $v) {
                if (is_int($k)) {
                    $prepend[] = $v;
                } else {
                    $defaults[$k] = $v;
                }
            }
            $arguments = array_merge($prepend, $defaults);
        }
        if ($single && isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        try {
            $arguments = array_values($arguments);
            $reflection = new \ReflectionClass($class);
            $constructor = $reflection->getConstructor();
            $instance = null;

            if ($constructor) {
                $arguments = $this->arguments($constructor, $arguments);
            }
            $instance = count($arguments) ? $reflection->newInstanceArgs($arguments) : new $reflection->name();

            if ($single && $instance) {
                $this->instances[$class] = $instance;
            }

            return $instance;
        } catch (\ReflectionException $e) {
            throw new DIException('Could not create instance - '.$e->getMessage());
        }
    }

    public function decorate($expression, callable $callback, $mode = 'after')
    {
        if (!is_array($expression)) {
            $expression = explode(',', $expression);
        }
        foreach ($expression as $e) {
            list($class, $method) = array_pad(preg_split('((->)|(::))', $e, 2), 2, '*');
            $class = trim($class);
            $method = trim($method);
            if (!isset($this->decorations[trim($class)])) {
                $this->decorations[trim($class)] = [];
            }
            if (!isset($this->decorations[$class][$method])) {
                $this->decorations[$class][$method] = [];
            }
            $this->decorations[$class][$method][] = [$mode, $callback];
        }
    }

    public function invoke($class, $method, array $arguments = [], array $construct = [])
    {
        $instance = is_string($class) ? $this->instance($class, $construct) : $class;
        $class = get_class($instance);

        try {
            $reflection = new \ReflectionMethod($instance, $method);
        } catch (\ReflectionException $e) {
            throw new DIException('Could not invoke method');
        }
        $arguments = $this->arguments($reflection, $arguments);

        $execute = [
            'before' => [],
            'after' => [],
        ];

        foreach ($this->decorations as $className => $methods) {
            if ($className === '*' || is_a($instance, $className)) {
                foreach ($methods as $methodName => $callbacks) {
                    if ($methodName === '*' || $method === $methodName) {
                        foreach ($callbacks as $cb) {
                            if (!isset($execute[$cb[0]])) {
                                $execute[$cb[0]] = [];
                            }
                            $execute[$cb[0]][] = $cb[1];
                        }
                    }
                }
            }
        }

        foreach ($execute['before'] as $cb) {
            call_user_func($cb, [
                'instance' => $instance,
                'class' => $class,
                'method' => $method,
                'arguments' => $arguments,
            ]);
        }

        try {
            $rslt = $reflection->invokeArgs($instance, $arguments);
        } catch (\ReflectionException $e) {
            throw new DIException('Error invoking method');
        }

        foreach ($execute['after'] as $cb) {
            call_user_func($cb, [
                'instance' => $instance,
                'class' => $class,
                'method' => $method,
                'arguments' => $arguments,
                'result' => $rslt,
            ]);
        }

        return $rslt;
    }
}
