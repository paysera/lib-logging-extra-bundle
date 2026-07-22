# Logging extra bundle

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

Symfony bundle for further Monolog, Sentry and Graylog integration.

## Why?

Monolog already offers integration with both Sentry and Graylog. This bundle re-uses those integrations, adding
the following features:
- clearer formatting for Graylog messages;
- adds correlation_id to correlate messages in Sentry with messages in Graylog from the same process;
- adds trace_id to correlate messages of one request across every service it touches;
- allows grouping some exceptions by their class, independently from where they were thrown at or what are their message;
- removes root prefix from messages (usually included in some exception messages);
- maps context to be available with logged sentry event. 

Also recommended configuration is given to allow nice synergy between Graylog and Sentry.

## Installation

```bash
composer require symfony/monolog-bundle sentry/sentry-symfony paysera/lib-logging-extra-bundle
```

Register installed bundles in your kernel or `bundles.php` file, if not installed automatically by flex.

## Configuration

This is recommended configuration for all three bundles.
This ensures that:
- `INFO` and above level log messages always goes to Graylog;
- in case `ERROR` and above level log message is received, all messages (even `DEBUG`, but maximum 50) goes to Graylog.
This helps to debug any problems as you get much more information about what has happened;
- `ERROR` level log messages goes to Sentry, so you can see any errors occurring in your application;
- logging does not break or impact your application, even if your Sentry or Graylog servers are down:
  - Graylog uses UDP, so no need to wait for responses. Handler is also wrapped in a failsafe to avoid any errors
  on DNS resolution impacting your application;
  - Sentry uses HTTP, but has error handling by default, also sends the messages on process shutdown; configuration
  overrides default 3 retries to just one – if sentry is overwhelmed with requests already, no need to send any
  more messages 3 times in a row;
- messages are visible on console. You can pass arguments to any command for further verbosity level to see INFO
or even DEBUG log messages.

```yaml

monolog:
    handlers:
        info:
            type: filter
            accepted_levels: [INFO, NOTICE, WARNING]
            handler: graylog_failsafe
        debug_and_errors:
            type: filter
            accepted_levels: [DEBUG, ERROR, CRITICAL, ALERT, EMERGENCY]
            handler: graylog_fingers_crossed
        console:
            type: console
            process_psr_3_messages: false
            channels: ["!event", "!doctrine"]
        sentry:
            type: service
            id: paysera_logging_extra.sentry_handler
        graylog_fingers_crossed:
            type: fingers_crossed
            action_level: error
            handler: graylog_failsafe
            stop_buffering: false
            buffer_size: 50
            nested: true
        graylog_failsafe:
            type: whatfailuregroup
            members: [graylog]
            nested: true
        graylog:
            type: gelf
            publisher:
                hostname: '%env(GRAYLOG_HOSTNAME)%'
                port: '%env(GRAYLOG_PORT)%'
                chunk_size: 8154
            formatter: paysera_logging_extra.formatter.gelf_message     # registered by the bundle
            nested: true

sentry:
    dsn: '%env(SENTRY_DSN)%'
    register_error_listener: false
    tracing:
        enabled: false # If using self-hosted Sentry version < v20.6.0
    options:
        environment: '%kernel.environment%'
        release: '%env(VERSION)%' # your app version, optional

paysera_logging_extra:
  application_name: app-something   # customise this to know which project message was sent from
```

### Trace ID

The `trace_id` field names an **entire request across every service it touches**. It is distinct from
`correlation_id`, which names a single hop: each service regenerates its own correlation id and records
the inbound one as `parent_corr_id`. So `correlation_id` finds the messages of one process, while
`trace_id` finds the messages of one request everywhere it went.

It works out of the box — there is nothing to configure. The bundle reads the `Paysera-Trace-Id` header
the public gateway stamps on every inbound request and records it as `trace_id` on every log line and as
a searchable Sentry tag. Bump the library and each service is traceable; no per-service code is needed.

The incoming value is treated as untrusted: it is recorded only when non-empty, at most 200 characters
(Sentry's tag-value limit), and made up of `[A-Za-z0-9._-]`. Anything else is ignored, so a hostile
header cannot inject control characters or bloat log lines. Requests without the header (and console
commands) simply carry no `trace_id` field, and the id is reset between reused iterations of a
long-running process (PHP-FPM, RoadRunner) so it cannot leak from one request into the next.

### Dual-write to stdout (VictoriaLogs)

To write every record to **both** Graylog and stdout (compact JSON Lines, collected by VictoriaLogs),
add a `stdout` handler using `paysera_logging_extra.formatter.stdout_json` and make it a member of the
existing `graylog_failsafe` `whatfailuregroup`. The `whatfailuregroup` isolates failures per member, so
Graylog being unreachable cannot break stdout and vice-versa. No per-call-site changes are needed, and
the existing Graylog delivery is untouched.

```yaml
monolog:
    handlers:
        graylog_failsafe:
            type: whatfailuregroup
            members: [graylog, stdout]   # add stdout as a second dual-write target
            nested: true
        stdout:
            type: stream
            path: 'php://stdout'
            level: debug                 # upstream filters/fingers_crossed already gate what reaches the group
            formatter: paysera_logging_extra.formatter.stdout_json   # registered by the bundle
            nested: true
```

Each stdout line is one compact JSON object: `timestamp` (ISO-8601, microseconds + offset),
`application_name`, `channel`, syslog `level` (DEBUG=7 … EMERGENCY=0), `level_name`, `message`,
optional `full_message` (the raw original when the message is exception-shaped), optional
`context`/`extra`, and top-level `correlation_id`, `parent_corr_id` and `trace_id` (all hoisted from
`extra`, and each omitted when absent). Lines are capped at 32766 bytes; oversize records stay
single-line, are flagged `truncated`, and are shrunk by dropping `full_message` first, then
`context`/`extra`, then truncating `message` UTF-8-safely, and finally dropping
`correlation_id`/`parent_corr_id`/`trace_id`. The three ids are hoisted so they survive the `extra`
drop: oversize records are mostly exception dumps, which is exactly where a request needs to stay
traceable.

Exception-shaped messages (`RuntimeException: boom in /app/src/Foo.php:42`) are split into a short
`message` and the raw `full_message`, identically to the canonical formatter in
`evp/lib-application-logging-bundle` (8.9.1/7.9.1). `Exception` matches capitalized or lowercase, and
the split is anchored at the first ` in /` file path — not at the first word `in` — so tails like
`in state NEW`, `in driver: ...` or SQL `IN (...)` stay in the short `message`. Messages without a
`/`-prefixed file path are left unsplit and emit no `full_message`.

## Usage

Log with INFO level and above to get messages in Graylog.

Log with ERROR level and above to get messages in Sentry.

Log with DEBUG level to get messages in Graylog in case any error occurs in the same request / process.
To find those, start with error in Sentry and search messages in Graylog by provided `correlation_id`.

To follow one request across services instead of within one process, search by `trace_id` (see
[Trace ID](#trace-id)).

## Semantic versioning

This bundle follows [semantic versioning](http://semver.org/spec/v2.0.0.html).

Public API of this bundle (in other words, you should only use these features if you want to easily update
to new versions):
- only services that are not marked as `public="false"`;
- only classes, interfaces and class methods that are marked with `@api`;
- console commands;
- supported DIC tags.

For example, if only class method is marked with `@api`, you should not extend that class, as constructor
could change in any release.

See [Symfony BC rules](https://symfony.com/doc/current/contributing/code/bc.html) for basic information
about what can be changed and what not in the API. Keep in mind, that in this bundle everything is
`@internal` by default.

## Running tests

```
composer update
composer test
```

## Contributing

Feel free to create issues and give pull requests.

### Running dependencies locally

```
cd example
docker-compose up -d
docker-compose exec sentry sentry upgrade
```

You'll find Graylog at [http://localhost:9001/](http://localhost:9001/) and Sentry at 
[http://localhost:9002/](http://localhost:9002/).

Open Graylog, login with `admin` `admin`, choose `System` -> `Inputs` -> `GELF UDP` -> `Launch new input` ->
input any title and select the node -> `Save`.

Open Sentry, login with user created by the last command, choose `Installation instructions` -> `Symfony2` ->
copy the credentials part (protocol, hostname and port might be missing).
Run following command, exchanging `HERE_GOES_CREDENTIALS` with real credentials:

```
export SENTRY_DSN=http://HERE_GOES_CREDENTIALS@localhost:9002/1
```

And run test PHP script:

```
php test.php
```

View logged data in Graylog and Sentry instances. Change the code for further
test scenarios or just use Graylog and Sentry to set-up and test your real 
project.

Cleanup afterwards:

```
docker-compose down
```

[ico-version]: https://img.shields.io/packagist/v/paysera/lib-logging-extra-bundle.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/paysera/lib-logging-extra-bundle/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/paysera/lib-logging-extra-bundle.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/paysera/lib-logging-extra-bundle.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/paysera/lib-logging-extra-bundle.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/paysera/lib-logging-extra-bundle
[link-travis]: https://travis-ci.org/paysera/lib-logging-extra-bundle
[link-scrutinizer]: https://scrutinizer-ci.com/g/paysera/lib-logging-extra-bundle/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/paysera/lib-logging-extra-bundle
[link-downloads]: https://packagist.org/packages/paysera/lib-logging-extra-bundle
