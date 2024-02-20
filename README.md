# DI

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)

PHP dependency injection / factory.

## Install

Via Composer

``` bash
$ composer require vakata/di
```

## Usage

``` php
namespace test;

$di = new \vakata\di\Container();

interface I { }
class A implements I {}
class B {
    public function __construct(I $instance) { }
}
class C {
    public function __construct($firstParam, $secondParam) { }
    public function test($arg = 1) { return 1; }
}

// this will register class A, and an alias I for class A as the class implements that interface
$di->register('\test\A'); 
$di->instance('\test\B'); // a B instance is created

// when registering you can prevent interfaces being added as aliases as adding the aliases manually
$di->register('\test\B', ['aliasedB']);

// you can also pass default constructor params, which can be named
$di->register('\test\C', null, ['secondParam' => 1, 2]);
$di->instance('\test\C'); // this will invoke \test\C::__construct(2,1);
$di->instance('\test\C', [2,4]); // this will invoke \test\C::__construct(2,4);

// you can also register instances:
$d = new D();
$di->register($d); // this is the same as \test\D

// you can also make sure that there is only one instance of a given class
$di->register('\some\class', ['aliases'], [/* default params */], true);

// aside from register and instance there is an invoke method
$di->invoke('\test\C', 'sum'); // returns 1

// you can also pass in arguments
$di->invoke('\test\C', 'sum', [2]); // returns 2

// and even constructor parameters
$di->invoke('\test\C', 'sum', [2], [5,6]);

// invoke works with instances too
$c1 = new C();
$di->invoke($c1, 'sum'); // returns 1
```

## Testing

``` bash
$ composer test
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email github@vakata.com instead of using the issue tracker.

## Credits

- [vakata][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information. 

[ico-version]: https://img.shields.io/packagist/v/vakata/di.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/vakata/di/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/vakata/di.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/vakata/di.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/vakata/di.svg?style=flat-square
[ico-cc]: https://img.shields.io/codeclimate/github/vakata/di.svg?style=flat-square
[ico-cc-coverage]: https://img.shields.io/codeclimate/coverage/github/vakata/di.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/vakata/di
[link-travis]: https://travis-ci.org/vakata/di
[link-scrutinizer]: https://scrutinizer-ci.com/g/vakata/di/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/vakata/di
[link-downloads]: https://packagist.org/packages/vakata/di
[link-author]: https://github.com/vakata
[link-contributors]: ../../contributors
[link-cc]: https://codeclimate.com/github/vakata/di

