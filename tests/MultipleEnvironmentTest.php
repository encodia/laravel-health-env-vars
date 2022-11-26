<?php

use Encodia\Health\Checks\EnvVars;
use Spatie\Health\Enums\Status;

const ENVIRONMENTS = ['staging', 'qa', 'production'];

it(
    "returns an error if vars are required in multiple environments and at least one var hasn't been set in the ".
    "current environment",
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
            ->shortSummary->toBe(trans('health-env-vars::translations.every_var_has_been_set'))
            ->notificationMessage->toBe(trans('health-env-vars::translations.every_var_has_been_set'));
    }
)->with(ENVIRONMENTS);
