# .env vars check for Spatie's Laravel Health

[![Latest Version on Packagist](https://img.shields.io/packagist/v/encodia/laravel-health-env-vars.svg?style=flat-square)](https://packagist.org/packages/encodia/laravel-health-env-vars)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/encodia/laravel-health-env-vars/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/encodia/laravel-health-env-vars/actions?query=workflow%3Arun-tests+branch%3Amain)
[![PHPStan](https://github.com/encodia/laravel-health-env-vars/actions/workflows/phpstan.yml/badge.svg?branch=main)](https://github.com/encodia/laravel-health-env-vars/actions/workflows/phpstan.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/encodia/laravel-health-env-vars/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/encodia/laravel-health-env-vars/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/encodia/laravel-health-env-vars.svg?style=flat-square)](https://packagist.org/packages/encodia/laravel-health-env-vars)

[Laravel Health](https://github.com/spatie/laravel-health) by [Spatie](https://spatie.be/),
in addition to providing some default checks, allows you to create your own.

This package checks if all variables you need have been set in your `.env` file.

Starting from **v1.8.0**, you can also ensure a variable has been set to a certain value.

Some variables are needed in every environment; others only in specific ones.
For example, you want to be sure that `BUGSNAG_API_KEY` has been set in your production
environment, but you don't need this while developing locally.

> Did anyone say "it works on my machine"?

Who has never lost several minutes before realizing that, let's say in `production`,
something is not working because one or more variables have not been valued?

## Requirements

`encodia/laravel-health-env-vars` requires **PHP 8.0+**, **Laravel 8.0+**.

**PHP 8.1+** is required with **Laravel 10**.

**PHP 8.2+** is required with **Laravel 11**.

## Installation

You can install the package via composer:

```bash
composer require encodia/laravel-health-env-vars
```

## Usage

Register this Check just like the others:

```php
// typically, in a service provider

use Spatie\Health\Facades\Health;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Encodia\Health\Checks\EnvVars;

Health::checks([
    // From Spatie's examples
    UsedDiskSpaceCheck::new()
        ->warnWhenUsedSpaceIsAbovePercentage(70)
        ->failWhenUsedSpaceIsAbovePercentage(90),
        
    // Many other checks...
    
    /*
     * Check that SOME_API_KEY and MAIL_FROM_ADDRESS variables are
     * set (no matter in which environment)
     */
    EnvVars::new()
        ->requireVars([
            'SOME_API_KEY',
            'MAIL_FROM_ADDRESS',
        ])
]);
```

Need to check only in a specific environment if a variable has been set?

No problem:

```php

use Spatie\Health\Facades\Health;
use Encodia\Health\Checks\EnvVars;

Health::checks([
    // ...
    // (other checks)
    // ...
    
    /*
     * Check that SOME_API_KEY and MAIL_FROM_ADDRESS variables are
     * set (no matter in which environment).
     * 
     * Only in staging, ensure EXTENDED_DEBUG_MODE has been set.
     * 
     * Additionally, only in production,
     * ensure BUGSNAG_API_KEY has been set.
     */
    EnvVars::new()
        ->requireVars([
            'SOME_API_KEY',
            'MAIL_FROM_ADDRESS',
        ])
        ->requireVarsForEnvironment('staging', [
            'EXTENDED_DEBUG_MODE'
        ])
        ->requireVarsForEnvironment('production', [
            'BUGSNAG_API_KEY'
        ]);
]);
```

It's very likely that you need some variables in multiple environments, but not in all of them.

For example, you need to set `BUGSNAG_API_KEY` only in these environments:

- `qa`
- `production`

but not in `local`, `staging`, `demo` or whatever.

You could chain multiple `requireVarsForEnvironment` calls but, in this case, it's better to
use `requireVarsForEnvironments`:

```php

use Spatie\Health\Facades\Health;
use Encodia\Health\Checks\EnvVars;

Health::checks([
    // ...
    // (other checks)
    // ...
    
    /*
     * Check that SOME_API_KEY and MAIL_FROM_ADDRESS variables are
     * set (no matter in which environment).
     * 
     * Only in staging, ensure EXTENDED_DEBUG_MODE has been set.
     * 
     * Additionally, only in qa and production environments,
     * ensure BUGSNAG_API_KEY has been set.
     */
    EnvVars::new()
        ->requireVars([
            'SOME_API_KEY',
            'MAIL_FROM_ADDRESS',
        ])
        ->requireVarsForEnvironment('staging', [
            'EXTENDED_DEBUG_MODE'
        ])
        ->requireVarsForEnvironments(['qa', 'production'], [
            'BUGSNAG_API_KEY'
        ]);
]);
```

Need to check if a variable has been set to a specific value?

Starting from **v1.8.0**, you can use `requireVarsMatchValues` to perform this check, regardless of the current
environment.

If you need to run this check only if the current environment matches the given one(s), you can
use `requireVarsForEnvironment` or `requireVarsForEnvironments`.

Examples:

```php

use Encodia\Health\Checks\EnvVars;
use Spatie\Health\Facades\Health;

Health::checks([
    EnvVars::new()
        // ... other methods ...
        ->requireVarsMatchValues([
            // Ensure that APP_LOCALE is set to 'en' (no matter which is the current environment)
            'APP_LOCALE' => 'en',
            // Ensure that APP_TIMEZONE is set to 'UTC' (no matter which is the current environment)
            'APP_TIMEZONE' => 'UTC',
        ])
        ->requireVarsMatchValuesForEnvironment('staging', [
            // Only if current environment is 'staging', we don't want to send e-mails to real customers
            'MAIL_MAILER' => 'log',        
        ])
        ->requireVarsMatchValuesForEnvironments(['qa', 'production'], [
            // Only if current environment is 'qa' or 'production, we want to log 'info' events or above
            'LOG_LEVEL' => 'info',
            // Only if current environment is 'qa' or 'production, we want to store assets to S3
            'FILESYSTEM_DISK' => 's3',        
        ]);
]);
```

⚠️ When checking values, do not store personal data, keys, tokens, etc.!

## Caveats

During your deployment process, be sure to run EnvVars checks **before**
caching your configuration!

Why? After running `php artisan config:cache`, any `env('WHATEVER_NAME')` will return `null`, so
your EnvVars checks will fail.

Please check

* [Laravel documentation](https://laravel.com/docs/9.x/configuration#configuration-caching)
* [env() Gotcha in Laravel When Caching Configuration](https://andy-carter.com/blog/env-gotcha-in-laravel-when-caching-configuration)

> [!IMPORTANT]  
> From version `1.9.0`, when configuration is cached (e.g. via `php artisan config:cache`), these checks are bypassed
> and they return `OK`.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Erik D'Ercole](https://github.com/eleftrik)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
