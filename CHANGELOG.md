# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 2.2.1
### Fixed
- Backport of 3.6.0. Formatters write a Symfony security token (`TokenInterface`) or user (`UserInterface`) as its `class` and `user_identifier` only, at any depth of the context. Before, both were expanded property by property, so a logged token carried its credentials, its attributes and its user's password into the GELF and line output. The user identifier is read with `getUserIdentifier()`, or `getUsername()` before Symfony 5.3; an identifier containing `@` is written as `[email]`. A token with no user, an empty identifier and a getter that throws all write `user_identifier` as `null`, and the log call goes on. An uninitialized Doctrine proxy user is still written as its id, and a token holding one writes `user_identifier` as `null`, so logging never loads that user. Only identifiers containing `@` are masked; other identifiers are written as they are. `symfony/security-core` is not a runtime dependency: the types are matched by name.

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
