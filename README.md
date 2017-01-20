# middlewares/payload

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]
[![Total Downloads][ico-downloads]][link-downloads]
[![SensioLabs Insight][ico-sensiolabs]][link-sensiolabs]

Parses the body of the request if it's not parsed and the method is POST, PUT or DELETE. It contains the following components to support different formats:

* [JsonPayload](#jsonpayload)
* [UrlEncodePayload](#urlencodepayload)
* [CsvPayload](#csvpayload)

## Requirements

* PHP >= 5.6
* A [PSR-7](https://packagist.org/providers/psr/http-message-implementation) http mesage implementation ([Diactoros](https://github.com/zendframework/zend-diactoros), [Guzzle](https://github.com/guzzle/psr7), [Slim](https://github.com/slimphp/Slim), etc...)
* A [PSR-15 middleware dispatcher](https://github.com/middlewares/awesome-psr15-middlewares#dispatcher)

## Installation

This package is installable and autoloadable via Composer as [middlewares/payload](https://packagist.org/packages/middlewares/payload).

```sh
composer require middlewares/payload
```

## JsonPayload

Parses the json payload of the request. Contains the following options to configure the [json_decode](http://php.net/manual/en/function.json-decode.php) function:

#### `associative(bool $associative)`

Enabled by default, convert the objects into associative arrays.

#### `depth(int $depth)`

To configure the recursion depth.

#### `options(int $options)`

To pass the bitmask of json_decode options.

#### `methods(array $methods)`

To configure the allowed methods. By default only the requests with the method `POST, PUT, PATCH, DELETE, COPY, LOCK, UNLOCK` are handled.

#### `override($override = true)`

To override the previous parsed body if exists (`false` by default)

```php
$dispatcher = new Dispatcher([
	(new Middlewares\JsonPayload())
		->associative(false)
		->depth(64)
]);

$response = $dispatcher->dispatch(new ServerRequest());
```

## UrlEncodePayload

Parses the url-encoded payload of the request. There's no options.

#### `methods(array $methods)`

To configure the allowed methods. By default only the requests with the method `POST, PUT, PATCH, DELETE, COPY, LOCK, UNLOCK` are handled.

#### `override($override = true)`

To override the previous parsed body if exists (`false` by default)

```php
$dispatcher = new Dispatcher([
    new Middlewares\UrlEncodePayload()
]);

$response = $dispatcher->dispatch(new ServerRequest());
```

## CsvPayload

Parses the csv payload of the request. Contains the following options to configure the [SplTempFileObject](http://php.net/SplTempFileObject) object:

#### `setCsvControl($delimiter = ",", $enclosure = "\"", $escape = "\\")`

To configure the Csv control characters. If the submitted character is invalid an `InvalidArgumentException` exception is thrown.

#### `associative(bool $associative)`

Enabled by default, convert the CSV into a sequential array of all CSV lines. Otherwise, return the CSV file as a `SplTempFileObject`.

#### `methods(array $methods)`

To configure the allowed methods. By default only the requests with the method `POST, PUT, PATCH, DELETE, COPY, LOCK, UNLOCK` are handled.

#### `override($override = true)`

To override the previous parsed body if exists (`false` by default)

```php
$dispatcher = new Dispatcher([
    new Middlewares\CsvPayload()
]);

$response = $dispatcher->dispatch(new ServerRequest());
```

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes and [CONTRIBUTING](CONTRIBUTING.md) for contributing details.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/middlewares/payload.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/middlewares/payload/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/middlewares/payload.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/middlewares/payload.svg?style=flat-square
[ico-sensiolabs]: https://img.shields.io/sensiolabs/i/7200be66-ac83-455c-bc85-c40eb569b94c.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/middlewares/payload
[link-travis]: https://travis-ci.org/middlewares/payload
[link-scrutinizer]: https://scrutinizer-ci.com/g/middlewares/payload
[link-downloads]: https://packagist.org/packages/middlewares/payload
[link-sensiolabs]: https://insight.sensiolabs.com/projects/7200be66-ac83-455c-bc85-c40eb569b94c
