# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased
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
