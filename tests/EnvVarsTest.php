<?php

use Encodia\Health\Checks\EnvVars;
use Spatie\Health\Enums\Status;

it('returns ok when no variable names have been provided', function () {
    $result = EnvVars::new()->run();

    expect($result)
        ->status->toBe(Status::ok())
        ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'))
        ->notificationMessage->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
});

it('returns ok when an empty set of variable names has been provided', function () {
    $result = EnvVars::new()
        ->requireVars([])
        ->run();

    expect($result)
        ->status->toBe(Status::ok())
        ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'))
        ->notificationMessage->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
});

it('returns ok when every provided name matches a .env variable with a non-empty value', function () {
    // GIVEN some .env variables which has been initialized with a non-empty value
    initEnvVars([
        'ENV_VAR1' => 'foo',
        'ENV_VAR2' => 'bar',
    ]);

    $result = EnvVars::new()
        ->requireVars([
            'ENV_VAR1',
            'ENV_VAR2',
        ])
        ->run();

    expect($result)
        ->status->toBe(Status::ok())
        ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'))
        ->notificationMessage->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
});

it('returns an error when not every provided name matches a .env variable with a non-empty value', function () {
    $missingList = ['ENV_VAR1'];

    // GIVEN some .env variables which has been initialized with a non-empty value and one variable which has an
    // empty value
    initEnvVars([
        'ENV_VAR1' => '',
        'ENV_VAR2' => 'bar',
    ]);

    $result = EnvVars::new()
        ->requireVars([
            'ENV_VAR1',
            'ENV_VAR2',
        ])
        ->run();

    expect($result)
        ->status->toBe(Status::failed())
        ->meta->toEqual($missingList)
        ->shortSummary->toBe(trans('health-env-vars::translations.not_every_var_has_been_set'))
        ->notificationMessage->toBe(
            trans('health-env-vars::translations.missing_vars_list', ['list' => implode(', ', $missingList)])
        );
});

it(
    'returns ok if environment specific vars have been set and the environment matches the current one',
    function () {
        $varName = 'ENV_PROD_VAR1';
        $specificEnvironment = 'production';

        mockCurrentEnvironment($specificEnvironment);
        initEnvVars([
            $varName => 'some_value',
        ]);
        // ensure code is running in the same environment we're testing that a var has been set
        expect(currentEnvironment())->toEqual($specificEnvironment);

        $result = EnvVars::new()
            ->requireVarsForEnvironment($specificEnvironment, [
                $varName,
            ])
            ->run();

        expect($result)
            ->status->toBe(Status::ok())
            ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'))
            ->notificationMessage->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
    }
);

test('environment specific vars are ignored if their environment does not match current one', function () {
    $varName = 'ENV_PROD_VAR1';
    $specificEnvironment = 'production';

    // ensure code is running in an environment different from the one we're testing a var has been set
    expect(app()->environment())->not->toEqual($specificEnvironment);
    // ensure in the current environment the given var has not been set
    initEnvVars([$varName => null]);

    $result = EnvVars::new()
        ->requireVarsForEnvironment('production', [
            $varName,
        ])
        ->run();

    expect($result)
        ->status->toBe(Status::ok())
        ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'))
        ->notificationMessage->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
});

it('returns an error if environment specific vars are required and they are not set in that environment', function () {
    $varName = 'ENV_PROD_VAR1';
    $missingList = [$varName];
    $specificEnvironment = 'production';

    // ensure code is running in an environment different from the one we're testing that a var has been set
    expect(currentEnvironment())->not->toEqual($specificEnvironment);
    // ensure in the current environment the given var has not been set
    initEnvVars([$varName => null]);

    // WHEN switching to the desired environment...
    mockCurrentEnvironment('production');

    $result = EnvVars::new()
        ->requireVarsForEnvironment('production', [
            $varName,
        ])
        ->run();

    expect($result)
        ->status->toBe(Status::failed())
        ->shortSummary->toBe(trans('health-env-vars::translations.not_every_var_has_been_set'))
        ->notificationMessage->toBe(
            trans('health-env-vars::translations.missing_vars_list_in_environment', [
                'environment' => currentEnvironment(),
                'list' => implode(', ', $missingList),
            ])
        );
});

test('several specific environment vars can be specified', function () {
    $result = EnvVars::new()
        ->requireVarsForEnvironment('staging', ['VAR1'])
        ->requireVarsForEnvironment('production', ['VAR2'])
        ->run();

    expect($result)
        ->status->toBe(Status::ok());
});
