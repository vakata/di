<?php

namespace vakata\di;

use RuntimeException;

/**
 * A minimal DI container.
 */
class DIContainer implements DIInterface
{
    protected array $replacements = [];
    protected array $instances = [];
    protected array $defaults = [];

    protected function arguments(\ReflectionMethod $method, array $args = []): array
    {
        $arguments = [];
        foreach ($method->getParameters() as $v) {
            // named first
            if ($v->getName() && isset($args[$v->getName()])) {
                $arguments[] = $args[$v->getName()];
                unset($args[$v->getName()]);
                continue;
            }
            // then with type hints (provided the next argument matches)
            $name = null;
            if ((int)PHP_VERSION >= 8) {
                $type = $v->getType();
                if ($type && $type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    $name = $type->getName();
                }
                if ($type && $type instanceof \ReflectionUnionType) {
                    $first = null;
                    foreach ($type->getTypes() as $t) {
                        if ($t && $t instanceof \ReflectionNamedType && !$t->isBuiltin()) {
                            $n = $t->getName();
                            if (!isset($first)) {
                                $first = $n;
                            }
                            if (count($args) && $args[0] instanceof $n) {
                                $name = $n;
                                break;
                            }
                        }
                    }
                    if (!isset($name)) {
                        $name = $first;
                    }
                }
            } else {
                if ($v->getClass()) {
                    $name = $v->getClass()->name;
                }
            }
            if ($name) {
                if (count($args) && $args[0] instanceof $name) {
                    $arguments[] = array_shift($args);
                    continue;
                }
                $last = null;
                try {
                    $temp = $this->instance('\\'.$name);
                } catch (\Throwable $e) {
                    $last = $e;
                    $temp = null;
                }
                if ($temp !== null) {
                    $arguments[] = $temp;
                    continue;
                }
                if ($v->isOptional()) {
                    $arguments[] = $v->getDefaultValue();
                    continue;
                }
                if ($v->allowsNull()) {
                    $arguments[] = null;
                    continue;
                }
                throw $last ?? new \RuntimeException();
            }
            // TODO: add scalar type hints and possibly better union types
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

    public function get(string $clss): object
    {
        $clss = trim($clss, '\\');
        if (isset($this->replacements[$clss])) {
            $clss = $this->replacements[$clss];
        }
        return $this->instances[$clss] ?? throw new RuntimeException('Instance not found');
    }
    public function has(string $clss): bool
    {
        $clss = trim($clss, '\\');
        if (isset($this->replacements[$clss])) {
            $clss = $this->replacements[$clss];
        }
        return isset($this->instances[$clss]);
    }
    public function defaults(string $clss, array $defaults): static
    {
        $this->defaults[trim($clss, '\\')] = $defaults;
        return $this;
    }
    public function alias(string $clss, array $aliases = [], bool $interfaces = false, bool $parents = false): static
    {
        $clss = trim($clss, '\\');
        if ($interfaces || $parents) {
            try {
                $reflection = new \ReflectionClass($clss);
                if ($interfaces) {
                    foreach ($reflection->getInterfaceNames() as $iname) {
                        $aliases[] = $iname;
                    }
                }
                if ($parents) {
                    $temp = $reflection;
                    while ($temp = $temp->getParentClass()) {
                        $aliases[] = $temp->getName();
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        foreach ($aliases as $name) {
            $this->replacements[trim($name, '\\')] = $clss;
        }
        return $this;
    }
    public function register(object $instance, bool $alias = true): static
    {
        $clss = get_class($instance);
        $this->instances[$clss] = $instance;
        if ($alias) {
            $this->alias($clss, [], true, true);
        }
        return $this;
    }
    public function instance(string $clss, array $arguments = [], bool $force = false): object
    {
        $clss = trim($clss, '\\');
        if (isset($this->replacements[trim($clss, '\\')])) {
            $clss = $this->replacements[$clss];
        }
        if (!$force && isset($this->instances[$clss])) {
            return $this->instances[$clss];
        }
        if (isset($this->defaults[$clss])) {
            foreach ($this->defaults[$clss] as $k => $v) {
                if (is_int($k)) {
                    $arguments[] = $v;
                } else if (!isset($arguments[$k])) {
                    $arguments[$k] = $v;
                }
            }
        }
        try {
            $reflection = new \ReflectionClass($clss);
            $constructor = $reflection->getConstructor();
            $instance = null;

            if ($constructor) {
                $arguments = $this->arguments($constructor, $arguments);
            } else {
                $arguments = [];
            }
            $instance = count($arguments) ? $reflection->newInstanceArgs($arguments) : new $reflection->name();
            return $instance;
        } catch (\ReflectionException $e) {
            throw new DIException('Could not create instance - '.$e->getMessage());
        }
    }
    public function invoke(string $clss, string $method, array $arguments = [], array $construct = []): mixed
    {
        $clss = trim($clss, '\\');
        if (isset($this->replacements[trim($clss, '\\')])) {
            $clss = $this->replacements[$clss];
        }
        try {
            $method = new \ReflectionMethod($clss, $method);
        } catch (\ReflectionException $e) {
            throw new DIException('Could not invoke method');
        }
        $arguments = $this->arguments($method, $arguments);
        if (!$method->isStatic()) {
            return $method->invokeArgs($this->instance($clss, $construct), $arguments);
        }
        return $method->invokeArgs(null, $arguments);
    }
}
