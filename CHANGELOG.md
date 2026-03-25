# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 3.3.0
### Added
- Sanitization of sensitive fields (password, secret, apiKey, etc.) in `FormatterTrait` object normalization

## 3.2.1
### Fixed
- Changed versions for graylog2/gelf-php

## 3.2.0
### Added
- Support the newer version of graylog2/gelf-php for 8.4 php

## 3.1.0
### Removed
- Removed support for PHP 7.2 version

### Fixed
- Fixed CI by adding ignored PHP and Symfony version matrix
- Fixed Monolog library support in all PHP and Symfony version ranges

## 3.0.0
### Changed
- Supporting version of sentry/sentry-symfony to ^5.3 
### Fixed
- Dependency on abandoned php-http/message-factory
### Removed
- Symfony 3 supporting

## 2.2.0
### Added
- Added Symfony ^6 support.

## 2.1.1 - 2023-04-13
### Fixed
- Fix support for Symfony 5.x

## 2.1.0 - 2023-03-29
### Added
- Added Symfony ^5 support.

## 2.0.0 - 2023-03-09
### Added
- PHP 8 support

## 1.0.2 - 2022-08-30
### Fixed
- Fixing deprecation error in symfony versions above 4.2

## 1.0.1 - 2021-03-18
### Changed
- Removed strict types in `FormatterTrait` method parameters, allowing for `monolog/monolog:^1.24` compatibility

## 1.0.0 - 2020-03-13
### Added
- Added `Paysera-Correlation-Id` header to response containing current request's correlation id
