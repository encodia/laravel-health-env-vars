<?php

use Encodia\Health\Checks\EnvVars;
use Spatie\Health\Enums\Status;

afterEach(function () {
    unsetEnvVars(['VAR1', 'VAR2', 'VAR3', 'VAR4', 'ENV_VAR1', 'ENV_VAR2', 'ENV_PROD_VAR1']);
});

it('returns ok when no variable names have been provided', function () {
    $result = EnvVars::new()->run();

    expect($result)
        ->status->toBe(Status::ok())
        ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
});

it('returns ok when an empty set of variable names has been provided', function () {
    $result = EnvVars::new()
        ->requireVars([])
        ->run();

    expect($result)
        ->status->toBe(Status::ok())
        ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
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
        ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
});

it('returns ok when a boolean variable is initialized to false', function () {
    initEnvVars([
        'ENV_VAR1' => false,
    ]);

    $result = EnvVars::new()
        ->requireVars([
            'ENV_VAR1',
        ])
        ->run();

    expect($result)
        ->status->toBe(Status::ok())
        ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
});

it('returns an error when not every provided name matches a .env variable with a non-empty value', function () {
    $missingList = ['ENV_VAR1'];

    // GIVEN one .env variable which has been initialized with a non-empty value and one variable which has not
    // been declared
    initEnvVars([
        'ENV_VAR2' => 'bar',
    ]);

    expect(env('ENV_VAR1'))->toBeNull();

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
    "returns an error if a var doesn't match the expected value in the current environment",
    function (string $currentEnvironment) {
        // ARRANGE

        $environments = ENVIRONMENTS;
        $variableName = 'VAR1';
        $variableExpectedValue = 'expected value';
        $variableActualValue = 'another value';
        $missingList = trans('health-env-vars::translations.var_not_matching_value', [
            'name' => $variableName,
            'actual' => EnvVars::displayableValueOf($variableActualValue),
            'expected' => EnvVars::displayableValueOf($variableExpectedValue),
        ]);

        expect($currentEnvironment)->toBeIn($environments);
        mockCurrentEnvironment($currentEnvironment);

        // init variable with a value different from the expected one
        initEnvVars([
            $variableName => $variableActualValue,
        ]);
        expect(env($variableName))->not->toEqual($variableExpectedValue);

        // ACT & ASSERT
        $result = EnvVars::new()
            ->requireVarsMatchValues([
                $variableName => $variableExpectedValue,
            ])
            ->run();

        expect($result)
            ->meta->toEqual([$variableName])
            ->status->toBe(Status::failed())
            ->shortSummary->toBe(trans('health-env-vars::translations.vars_not_matching_values'))
            ->notificationMessage->toBe(
                trans('health-env-vars::translations.vars_not_matching_values_list', [
                    'list' => $missingList,
                ])
            );
    }
)->with([ENVIRONMENT_STAGING]);

it(
    'returns OK if all vars match the expected values in the current environment',
    function (string $currentEnvironment) {
        // ARRANGE

        $environments = ENVIRONMENTS;
        $varsWithValues = [
            'VAR1' => 'Some value',
            'VAR2' => '42',
            'VAR3' => false,
        ];

        expect($currentEnvironment)->toBeIn($environments);
        mockCurrentEnvironment($currentEnvironment);

        // init variable with a value different from the expected one
        initEnvVars($varsWithValues);

        // ACT & ASSERT
        $result = EnvVars::new()
            ->requireVarsMatchValues($varsWithValues)
            ->run();

        expect($result)
            ->status->toBe(Status::ok())
            ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
    }
)->with([ENVIRONMENT_STAGING]);

it(
    "fails if at least one var doesn't match its value for the current environment ",
    function (string $environment) {
        // ARRANGE
        mockCurrentEnvironment($environment);

        $variableName = 'VAR2';
        $variableActualValue = 'different';
        $variableExpectedValue = 'bar';
        $vars = [
            'VAR1' => 'foo',
            $variableName => $variableActualValue,
        ];
        initEnvVars($vars);

        // ACT & ASSERT
        $missingList = trans('health-env-vars::translations.var_not_matching_value', [
            'name' => $variableName,
            'actual' => EnvVars::displayableValueOf($variableActualValue),
            'expected' => EnvVars::displayableValueOf($variableExpectedValue),
        ]);

        $result = EnvVars::new()
            ->requireVarsMatchValuesForEnvironment($environment, [
                'VAR1' => 'foo',
                'VAR2' => $variableExpectedValue,
            ])
            ->run();

        expect($result)
            ->status->toBe(Status::failed())
            ->shortSummary->toBe(trans('health-env-vars::translations.vars_not_matching_values'))
            ->notificationMessage->toBe(
                trans('health-env-vars::translations.vars_not_matching_values_list', [
                    'list' => $missingList,
                ])
            );
    }
)->with([ENVIRONMENT_STAGING]);

it('returns OK if vars match their values for the current environment ', function (string $environment) {
    // ARRANGE
    mockCurrentEnvironment($environment);
    $vars = [
        'VAR1' => 'foo',
    ];
    initEnvVars($vars);

    // ACT & ASSERT
    $result = EnvVars::new()
        ->requireVarsMatchValuesForEnvironment($environment, $vars)
        ->run();

    expect($result)
        ->status->toBe(Status::ok())
        ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
})->with([ENVIRONMENT_STAGING]);

it('returns OK if vars does not match their values in another environment ', function (string $environment) {
    // ARRANGE
    mockCurrentEnvironment($environment);
    $vars = [
        'VAR1' => 'foo',
    ];
    initEnvVars($vars);

    // ACT & ASSERT
    $result = EnvVars::new()
        ->requireVarsMatchValuesForEnvironment(ENVIRONMENT_PRODUCTION, $vars)
        ->run();

    expect($result)
        ->status->toBe(Status::ok())
        ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
})->with([ENVIRONMENT_STAGING]);
