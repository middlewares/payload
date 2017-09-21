# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [0.5.0] - 2017-09-21

### Changed

* The `contentType()` argument is an array instead a string, allowing to assign multiple values
* Append `.dist` suffix to phpcs.xml and phpunit.xml files
* Changed the configuration of phpcs and php_cs
* Upgraded phpunit to the latest version and improved its config file
* Updated to `http-interop/http-middleware#0.5`

## [0.4.0] - 2017-02-05

* Added
  * New option `contentType()` to configure the `Content-Type` request header
  * Improve CsvPayload
    - New option `delimiter()` to configure the CSV delimiter character
    - New option `enclosure()` to configure the CSV enclosure character
    - New option `escape()` to configure the CSV escape character

* Fixed
  * CsvPayload: `StreamInterface` fixed left undetached

## [0.3.0] - 2016-12-26

### Changed

* Updated tests
* Updated to `http-interop/http-middleware#0.4`
* Updated `friendsofphp/php-cs-fixer#2.0`

## [0.2.0] - 2016-11-27

### Changed

* Updated to `http-interop/http-middleware#0.3`

### Added

* New option `methods()` to configure the allowed methods
* New option `override()` to configure if the previous parsed body must be overrided

## 0.1.0 - 2016-10-04

First version

[0.5.0]: https://github.com/middlewares/payload/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/middlewares/payload/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/middlewares/payload/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/middlewares/payload/compare/v0.1.0...v0.2.0
