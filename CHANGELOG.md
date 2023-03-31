# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2023-03-31
### Removed
- Dropped support for versions below PHP 8.1 and Symfony 5.
- Root prefix processor as messages can no longer be altered as the record is now an entity instead of array, and the `message` field is read-only.
### Changed
- Changed some logic to fit the new versions of the used libraries.

## [2.1.0] - 2023-03-29
### Added
- Added Symfony ^5 support.

## [2.0.0] - 2023-03-09
### Added
- PHP 8 support

## [1.0.2] - 2022-08-30
### Fixed
- Fixing deprecation error in symfony versions above 4.2

## [1.0.1] - 2021-03-18
### Changed
- Removed strict types in `FormatterTrait` method parameters, allowing for `monolog/monolog:^1.24` compatibility

## [1.0.0] - 2020-03-13
### Added
- Added `Paysera-Correlation-Id` header to response containing current request's correlation id
