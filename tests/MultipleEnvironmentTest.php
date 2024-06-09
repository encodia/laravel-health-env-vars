<?php

use Encodia\Health\Checks\EnvVars;
use Spatie\Health\Enums\Status;

afterEach(function () {
    unsetEnvVars(['VAR1', 'VAR2', 'VAR3', 'VAR4', 'ENV_PROD_VAR1']);
});

describe('when vars need to be set', function () {
    it(
        'returns ok if environment specific vars have been set and the environment matches the current one',
        function () {
            $varName = 'ENV_PROD_VAR1';
            $specificEnvironment = ENVIRONMENT_PRODUCTION;

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
                ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
        }
    );

    test('environment specific vars are ignored if their environment does not match current one', function () {
        $varName = 'ENV_PROD_VAR1';
        $specificEnvironment = ENVIRONMENT_PRODUCTION;

        // ensure code is running in an environment different from the one we're testing a var has been set
        expect(app()->environment())->not->toEqual($specificEnvironment);
        // ensure in the current environment the given var has not been set
        initEnvVars([$varName => null]);

        $result = EnvVars::new()
            ->requireVarsForEnvironment(ENVIRONMENT_PRODUCTION, [
                $varName,
            ])
            ->run();

        expect($result)
            ->status->toBe(Status::ok())
            ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
    });

    it(
        'returns an error if environment specific vars are required and they are not set in that environment',
        function () {
            $varName = 'ENV_PROD_VAR1';
            $missingList = [$varName];
            $specificEnvironment = ENVIRONMENT_PRODUCTION;

            // ensure code is running in an environment different from the one we're testing that a var has been set
            expect(currentEnvironment())->not->toEqual($specificEnvironment);
            // ensure in the current environment the given var has not been set
            initEnvVars([$varName => null]);

            // WHEN switching to the desired environment...
            mockCurrentEnvironment(ENVIRONMENT_PRODUCTION);

            $result = EnvVars::new()
                ->requireVarsForEnvironment(ENVIRONMENT_PRODUCTION, [
                    $varName,
                ])
                ->run();

            expect($result)
                ->status->toBe(Status::failed())
                ->shortSummary->toBe(trans('health-env-vars::translations.not_every_var_has_been_set'))
                ->notificationMessage->toBe(
                    trans('health-env-vars::translations.missing_vars_list', [
                        'list' => implode(', ', $missingList),
                    ])
                );
        }
    );

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
                    trans('health-env-vars::translations.missing_vars_list', [
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

    test('several specific environment vars can be specified', function () {
        $result = EnvVars::new()
            ->requireVarsForEnvironment(ENVIRONMENT_STAGING, ['VAR1'])
            ->requireVarsForEnvironment(ENVIRONMENT_PRODUCTION, ['VAR2'])
            ->run();

        expect($result)
            ->status->toBe(Status::ok());
    });
});

describe('when vars need to match values for multiple environments', function () {
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
