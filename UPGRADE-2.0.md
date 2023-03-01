# Upgrade 1.x to 2.0
The `sentry/sentry-symfony` package is updated to ^4.0 which requires changes in the configuration if you use the recommended configuration:

- Remove `sentry.monolog` configuration option  

Before:
```yaml
sentry:
    dsn: '%env(SENTRY_DSN)%'
    register_error_listener: false
    monolog:
        error_handler:
            enabled: true
            level: error
    options:
        environment: '%kernel.environment%'
        release: '%env(VERSION)%' # your app version, optional
        send_attempts: 1
```

After:
```yaml
sentry:
    dsn: '%env(SENTRY_DSN)%'
    register_error_listener: false
    options:
        environment: '%kernel.environment%'
        release: '%env(VERSION)%' # your app version, optional
        send_attempts: 1
```

- Update the sentry monolog handler to `paysera_logging_extra.sentry_handler`

Before:
```yaml
monolog:
    handlers:
        ...
        sentry:
            type: service
            id: Sentry\Monolog\Handler
        ...
```

After:
```yaml
monolog:
    handlers:
        ...
        sentry:
            type: service
            id: paysera_logging_extra.sentry_handler
        ...
```

- Due to a bug in all versions below 6.0 of the SensioFrameworkExtraBundle bundle, you will likely receive an error during the building of symfony container related to the missing `Nyholm\Psr7\Factory\Psr17Factory` class. To workaround the issue, if you are not using the PSR-7 bridge, please change the configuration of that bundle as follows:

```yaml
sensio_framework_extra:
   psr_message:
      enabled: false
```

For more details about the issue see https://github.com/sensiolabs/SensioFrameworkExtraBundle/pull/710.

- The version of `sentry/sentry` was upgraded to ^3.0. If you're using self-hosted Sentry version < v20.6.0 then you should disable the tracing as it uses the envelope endpoint which requires Sentry version >= v20.6.0 to work.

```yaml
sentry:
  tracing:
    enabled: false
```

Check the [UPGRADE-3.0.md](https://github.com/getsentry/sentry-php/blob/master/UPGRADE-3.0.md) file of `sentry/sentry` for other notable updates.   
Check the [UPGRADE-4.0.md](https://github.com/getsentry/sentry-symfony/blob/4.6.0/UPGRADE-4.0.md) file of `sentry/sentry-symfony` for other notable updates.
