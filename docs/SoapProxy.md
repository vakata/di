# vakata\di\SoapProxy
A special class for using a DIContainer with a PHP SOAP server.

## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\di\soapproxy__construct)|Create an instance to pass to $soap->handle().|

---



### vakata\di\SoapProxy::__construct
Create an instance to pass to $soap->handle().  


```php
public function __construct (  
    \DIInterface $dic,  
    string $class  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$dic` | `\DIInterface` | the DI container instance |
| `$class` | `string` | the class name |

---

