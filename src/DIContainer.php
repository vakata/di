<?php

namespace vakata\di;

/**
 * A minimal DI container.
 */
class DIContainer implements DIInterface
{
    protected $replacements = [];
    protected $instances = [];
    protected $decorations = [];

    /**
     * @codeCoverageIgnore
     */
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

    /**
     * Register a class name or an instance in the container.
     * Unregistered classes will be created too, the idea of this method is to create aliases for a class.
     * If aliases are not specified the interfaces that the class implements will be used.
     * @method register
     * @param  mixed    $class    the class string (or instance)
     * @param  array|string|null   $alias    the aliases to which this class responds
     * @param  array    $defaults defaults to use when creating instances
     * @param  boolean  $single   should only a single instance of this class exist
     * @return self
     */
    public function register($class, $alias = null, array $defaults = [], $single = false)
    {
        if (is_object($class)) {
            $single = true;
            $temp = get_class($class);
            $this->instances[$temp] = $class;
            $class = $temp;
        }
        if ($alias === null) {
            $reflection = new \ReflectionClass($class);
            $alias = $reflection->getInterfaceNames();
        }
        if (!is_array($alias)) {
            $alias = [$alias];
        }
        $alias[] = $class;
        foreach ($alias as $name) {
            $this->replacements[trim($name, '\\')] = [$class, $defaults, $single];
        }

        return $this;
    }
    /**
     * Create an instance of a class.
     * @method instance
     * @param  string   $class     the class name (or registered alias)
     * @param  array    $arguments optional arguments to use when creating the instance
     * @return mixed               the class instance
     */
    public function instance($class, array $arguments = [])
    {
        $defaults = [];
        $single = false;
        if (isset($this->replacements[trim($class, '\\')])) {
            list($class, $defaults, $single) = $this->replacements[trim($class, '\\')];
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
        if ($single && isset($this->instances[trim($class, '\\')])) {
            return $this->instances[trim($class, '\\')];
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
                $this->instances[trim($class, '\\')] = $instance;
            }

            return $instance;
        } catch (\ReflectionException $e) {
            throw new DIException('Could not create instance - '.$e->getMessage());
        }
    }
    /**
     * Invoke a method of a class.
     * @method invoke
     * @param  mixed  $class     the class name or an instance to use
     * @param  string $method    the method name to execute
     * @param  array  $arguments optional array of arguments to invoke with
     * @param  array  $construct optional array of arguments to construct the class instance with (if $class is string)
     * @return mixed             the result of the method execution
     */
    public function invoke($class, $method, array $arguments = [], array $construct = [])
    {
        $class = is_string($class) ? $this->instance($class, $construct) : $class;

        try {
            $method = new \ReflectionMethod($class, $method);
        } catch (\ReflectionException $e) {
            throw new DIException('Could not invoke method');
        }
        $arguments = $this->arguments($method, $arguments);
        return $method->invokeArgs($class, $arguments);
    }

    public function __call($method, array $arguments = [])
    {
        return $this->instance($method, $arguments);
    }
}

/**
 * Attach a function to be executed before or after executing a given method (provided that `invoke` is used).
 * The function will receive an array as a single parameter, containing the following keys:
 *  * instance - the instance of the class
 *  * class - string, the class name
 *  * method - string, the method name being executed
 *  * arguments - array, the data the method is executed with
 *  * result - the result of the execution, only available if the function was registered with `after`
 * @method decorate
 * @param  string   $expression the class & method to decorate - for example class::method or class::* or *::*
 * @param  callable $callback   the function to execute
 * @param  string   $mode       should the function be executed `before` or `after` the method, defaults to `after`
 * @return self
 */
/*
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
    return $this;
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
*/
