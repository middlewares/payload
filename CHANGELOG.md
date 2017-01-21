# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## Next

* Improve CsvPayload

    - New option `delimiter()` to configure the CSV delimiter character
    - New option `enclosure()` to configure the CSV enclosure character
    - New option `escape()` to configure the CSV escape character
    - `StreamInterface` fixed left undetached

## 0.3.0 - 2016-12-26

### Changed

* Updated tests
* Updated to `http-interop/http-middleware#0.4`
* Updated `friendsofphp/php-cs-fixer#2.0`

## 0.2.0 - 2016-11-27

### Changed

* Updated to `http-interop/http-middleware#0.3`

### Added

* New option `methods()` to configure the allowed methods
* New option `override()` to configure if the previous parsed body must be overrided

## 0.1.0 - 2016-10-04

First version
