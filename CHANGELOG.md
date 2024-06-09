# Changelog

All notable changes to `laravel-health-env-vars` will be documented in this file.

## v.1.8.0 - 2024-06-09

### added

- add `requireVarsMatchValues`: check if the given variables have been set to the given values
- add `requireVarsForEnvironment` and `requireVarsForEnvironments`: same as `requireVarsMatchValues`, but check is
  performed only if the current environment matches the given one(s)

### changed

- refactor code

## v.1.7.0 - 2024-03-13

### added

- Laravel 11 support

## v.1.6.0 - 2023-03-05

### added

- Laravel 10 support

## v.1.5.1 - 2023-02-21

### fixed

- prevent sending notifications when status is OK (thanks to [https://github.com/maxkalahur]())

## 1.5.1 - 2023-02-21

### fixed

- prevent sending notifications when status is OK (thanks to [https://github.com/maxkalahur]())

## 1.5.0 - 2023-01-14

### added

- support for PHP `8.2`
- chore: use Laravel Pint

## 1.4.0 - 2022-11-26

### added

- add `requireVarsForEnvironments`: check if some variables have been set only in the given environments (
- e.g. `['qa', 'production']`)
- more comments in the code

### fix

- typos

## 1.3.1 - 2022-10-05

### fixed

- change condition to fix PHPStan check

## 1.3.0 - 2022-10-05

### added

- improve documentation adding *Caveats* section in README.md

## 1.2.3 - 2022-03-24

### fix

- typo (again...)

## 1.2.1 - 2022-03-03

### fixed

- remove duplicates from README.md
- update run-test GitHub action - same specs as spatie/laravel-health

## 1.2.0 - 2022-03-01

### added

- Laravel 8 support

## 1.0.1 - 2022-02-28

### fixed

- translations didn't work

## 1.0.0 - 2022-02-27

- initial release
