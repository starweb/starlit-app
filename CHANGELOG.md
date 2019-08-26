# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [unreleased]
### Added
- support for reflection based dependency injection 
- resolve controller actions via container
- CHANGELOG.md
- strict type declarations to all classes (including tests)
- type hinting for method parameters and return types
- contracts for Router and View classes
- php7.3 to travis config

### Changed
- updated phpunit package and tests

### Removed
- version from composer.json

## [0.5.0] - 2018-09-18
### Changed
- Updated dependencies to PHP >=7.1 and Symfony ^4.0
- Updated starlit inter-package dependencies
- Updated Travis CI to run tests on PHP 7.1 and 7.2

## [0.4.0] - 2018-08-09
### Added
- Extends the routing by adding support for:
  - route names
  - HTTP methods

## [0.3.1] - 2017-03-04
### Changed
- Update composer.json.

## [0.3.0] - 2017-02-27
### Changed
- Bootable service providers.
- App container methods moved into separate Container class.
- Container now implements PSR-11.

## [0.2.1] - 2017-01-30
### Changed
- Set new request properties when forwarding.

## [0.2.0] - 2017-01-22
### Added
- support for more clean app structure for non module projects.

## [0.1.0] - 2016-03-31
### Added
- initial release