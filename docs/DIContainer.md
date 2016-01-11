# vakata\di\DIContainer
A minimal DI container.

## Methods

| Name | Description |
|------|-------------|
|[register](#vakata\di\dicontainerregister)|Register a class name or an instance in the container.|
|[instance](#vakata\di\dicontainerinstance)|Create an instance of a class.|
|[invoke](#vakata\di\dicontainerinvoke)|Invoke a method of a class.|

---



### vakata\di\DIContainer::register
Register a class name or an instance in the container.  
Unregistered classes will be created too, the idea of this method is to create aliases for a class.  
If aliases are not specified the interfaces that the class implements will be used.

```php
public function register (  
    mixed $class,  
    array|string|null $alias,  
    array $defaults,  
    boolean $single  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$class` | `mixed` | the class string (or instance) |
| `$alias` | `array`, `string`, `null` | the aliases to which this class responds |
| `$defaults` | `array` | defaults to use when creating instances |
| `$single` | `boolean` | should only a single instance of this class exist |
|  |  |  |
| `return` | `self` |  |

---


### vakata\di\DIContainer::instance
Create an instance of a class.  


```php
public function instance (  
    string $class,  
    array $arguments  
) : mixed    
```

|  | Type | Description |
|-----|-----|-----|
| `$class` | `string` | the class name (or registered alias) |
| `$arguments` | `array` | optional arguments to use when creating the instance |
|  |  |  |
| `return` | `mixed` | the class instance |

---


### vakata\di\DIContainer::invoke
Invoke a method of a class.  


```php
public function invoke (  
    mixed $class,  
    string $method,  
    array $arguments,  
    array $construct  
) : mixed    
```

|  | Type | Description |
|-----|-----|-----|
| `$class` | `mixed` | the class name or an instance to use |
| `$method` | `string` | the method name to execute |
| `$arguments` | `array` | optional array of arguments to invoke with |
| `$construct` | `array` | optional array of arguments to construct the class instance with (if $class is string) |
|  |  |  |
| `return` | `mixed` | the result of the method execution |

---

