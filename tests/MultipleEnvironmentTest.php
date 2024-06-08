<?php

use Encodia\Health\Checks\EnvVars;
use Spatie\Health\Enums\Status;

const ENVIRONMENT_STAGING = 'staging';
const ENVIRONMENT_QA = 'qa';
const ENVIRONMENT_PRODUCTION = 'production';

const ENVIRONMENTS = [ENVIRONMENT_STAGING, ENVIRONMENT_QA, ENVIRONMENT_PRODUCTION];

afterEach(function () {
    unsetEnvVars(['VAR1', 'VAR2', 'VAR3', 'VAR4']);
});

describe('when vars need to match values', function () {
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
                'actual' => $variableActualValue,
                'expected' => $variableExpectedValue,
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
                        'environment' => currentEnvironment(),
                        'list' => $missingList,
                    ])
                );
        }
    )->with(ENVIRONMENTS);

    it(
        'returns OK if all vars match the expected values in the current environment',
        function (string $currentEnvironment) {
            // ARRANGE

            $environments = ENVIRONMENTS;
            $varsWithValues = [
                'VAR1' => 'Some value',
                'VAR2' => 42,
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
    )->with(ENVIRONMENTS);
});

describe('when vars need to match values for a single environment', function () {
    it("fails if at least one var doesn't match its value for the current environment ", function (string $environment) {
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
            'actual' => $variableActualValue,
            'expected' => $variableExpectedValue,
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
                    'environment' => currentEnvironment(),
                    'list' => $missingList,
                ])
            );
    })->with([ENVIRONMENT_STAGING]);

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
});

describe('when vars need to match values for multiple environments', function () {
    it("fails if at least one var doesn't match its value for the current environment ", function (string $environment) {
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
            'actual' => $variableActualValue,
            'expected' => $variableExpectedValue,
        ]);

        $result = EnvVars::new()
            ->requireVarsMatchValuesForEnvironments(ENVIRONMENTS, [
                'VAR1' => 'foo',
                'VAR2' => $variableExpectedValue,
            ])
            ->run();

        expect($result)
            ->status->toBe(Status::failed())
            ->shortSummary->toBe(trans('health-env-vars::translations.vars_not_matching_values'))
            ->notificationMessage->toBe(
                trans('health-env-vars::translations.vars_not_matching_values_list', [
                    'environment' => currentEnvironment(),
                    'list' => $missingList,
                ])
            );
    })->with([ENVIRONMENT_STAGING]);

    it('returns OK if vars match their values for the current environment ', function (string $environment) {
        // ARRANGE
        mockCurrentEnvironment($environment);
        $vars = [
            'VAR1' => 'foo',
        ];
        initEnvVars($vars);

        // ACT & ASSERT
        $result = EnvVars::new()
            ->requireVarsMatchValuesForEnvironments(ENVIRONMENTS, $vars)
            ->run();

        expect($result)
            ->status->toBe(Status::ok())
            ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
    })->with([ENVIRONMENT_STAGING]);

    it('returns OK if vars does not match their values in another environment ', function (string $environment) {
        // ARRANGE
        mockCurrentEnvironment($environment);

        $variableName = 'VAR1';
        $variableActualValue = 'different';
        $variableExpectedValue = 'bar';

        $vars = [
            $variableName => $variableActualValue,
        ];
        initEnvVars($vars);

        // ACT & ASSERT
        $result = EnvVars::new()
            ->requireVarsMatchValuesForEnvironments(
                [ENVIRONMENT_QA, ENVIRONMENT_PRODUCTION],
                [$variableName => $variableExpectedValue]
            )
            ->run();

        expect($result)
            ->status->toBe(Status::ok())
            ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
    })->with([ENVIRONMENT_STAGING]);
});

it(
    "returns an error if vars are required in multiple environments and at least one var hasn't been set in the ".
    'current environment',
    function (string $currentEnvironment) {
        $environments = ENVIRONMENTS;
        $vars = ['VAR1', 'VAR2'];
        $missingList = ['VAR1'];

        expect($currentEnvironment)->toBeIn($environments);
        mockCurrentEnvironment($currentEnvironment);

        // init only VAR2 env var
        initEnvVars([
            'VAR2' => 'somevalue',
        ]);

        // ensure VAR1 hasn't been set
        expect(env('VAR1'))->toBeNull();

        $result = EnvVars::new()
            ->requireVarsForEnvironments($environments, $vars)
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
    }
)->with(ENVIRONMENTS);

it(
    'returns ok if vars are required in multiple environments and they have been set in the current one (one of them)',
    function (string $currentEnvironment) {
        $environments = ENVIRONMENTS;
        $vars = ['VAR3', 'VAR4'];

        expect($currentEnvironment)->toBeIn($environments);
        mockCurrentEnvironment($currentEnvironment);

        // init env vars using the given names and assigning a value
        initEnvVars(
            collect($vars)->mapWithKeys(function ($name) {
                return [$name => 'somevalue'];
            })->toArray()
        );

        $result = EnvVars::new()
            ->requireVarsForEnvironments($environments, $vars)
            ->run();

        expect($result)
            ->status->toBe(Status::ok())
            ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
    }
)->with(ENVIRONMENTS);
