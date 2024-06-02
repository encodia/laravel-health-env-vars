<?php

use Encodia\EnvVars\Tests\TestCase;
use Illuminate\Support\Facades\App;

uses(TestCase::class)->in(__DIR__);

/**
 * Initialize .env variables with the given names and values
 *
 * @param  array<string, string>  $vars
 */
function initEnvVars(array $vars): void
{
    foreach ($vars as $name => $value) {
        putenv("$name=$value");
    }
}

/**
 * Unset the given .env variables.
 */
function unsetEnvVars(array $vars): void
{
    foreach ($vars as $name) {
        putenv($name);
    }
}

/**
 * Make Laravel 'think' code is running in $environment environment
 * and return the current one
 */
function mockCurrentEnvironment(string $environment): string
{
    // This approach should be avoided, because this method it's both a Query and a Command.
    // But it's used only as a helper for tests.
    App::partialMock()
        ->shouldReceive('environment')
        ->andReturn($environment);

    return $environment;
}

/*
 * Helper which returns the current environment, using the same method as mockCurrentEnvironment,
 * which mocks the environment during tests
 */
function currentEnvironment(): string
{
    return App::environment();
}
