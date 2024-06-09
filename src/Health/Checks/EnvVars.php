<?php

declare(strict_types=1);

namespace Encodia\Health\Checks;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class EnvVars extends Check
{
    /** @var Collection<int,string> */
    protected Collection $requiredVars;

    /** @var Collection<string,mixed> */
    protected Collection $requiredVarsWithValues;

    /** @var Collection<string,Collection<int,string>> */
    protected Collection $environmentSpecificVars;

    /** @var Collection<string,Collection<string,mixed>> */
    protected Collection $environmentSpecificVarsWithValues;

    /**
     * Run the check and return the Result.
     */
    public function run(): Result
    {
        $this->requiredVars ??= collect();
        $this->environmentSpecificVars ??= collect();

        $this->requiredVarsWithValues ??= collect();
        $this->environmentSpecificVarsWithValues ??= collect();

        /** @var string $currentEnvironment */
        $currentEnvironment = App::environment();

        $result = Result::make();

        // Check variables match their values

        // Merge the non-environment-specific collection with the current-environment-specific one
        $requiredVarsWithValues = $this->requiredVarsWithValues->merge(
            $this->environmentSpecificVarsWithValues->get($currentEnvironment) ?? collect()
        );

        $check = $this->checkRequiredVarsWithValues($requiredVarsWithValues);

        if ($check->hasFailed()) {
            return $result->meta($check->meta)
                ->shortSummary($check->summary)
                ->failed($check->message);
        }

        // Check all provided variable names match .env variables with non-empty value

        // Merge the non-environment-specific collection with the current-environment-specific one
        $requiredVars = $this->requiredVars->merge(
            $this->environmentSpecificVars->get($currentEnvironment) ?? []
        );
        $missingVars = $this->missingVars($requiredVars);

        if ($missingVars->count() > 0) {
            return $result->meta($missingVars->toArray())
                ->shortSummary(trans('health-env-vars::translations.not_every_var_has_been_set'))
                ->failed(
                    trans('health-env-vars::translations.missing_vars_list', [
                        'list' => $missingVars->implode(','),
                    ])
                );
        }

        return $result->ok()
            ->shortSummary(trans('health-env-vars::translations.every_var_has_been_set'));
    }

    /**
     * Require the given variable names to be set (no matter in which environment).
     *
     * @param  array<int,string>  $names
     * @return $this
     */
    public function requireVars(array $names): self
    {
        $this->requiredVars = collect($names);

        return $this;
    }

    /**
     * Require the given variable names to be set (no matter in which environment) to the
     * values supplied.
     *
     * @param  array<string,mixed>  $values
     * @return $this
     */
    public function requireVarsMatchValues(array $values): self
    {
        $this->requiredVarsWithValues = collect($values);

        return $this;
    }

    /**
     * Require the given variable names to be set in the given environment.
     *
     * @param  array<int,string>  $names
     * @return $this
     */
    public function requireVarsForEnvironment(string $environment, array $names): self
    {
        // This method could be called several times (e.g. for different environments)

        $this->environmentSpecificVars ??= Collection::empty();

        if (! $this->environmentSpecificVars->has($environment)) {
            $this->environmentSpecificVars->put($environment, collect($names));
        }

        return $this;
    }

    /**
     * Require the given variable names to be set, in the current environment, to the values supplied.
     *
     * @param  array<string,mixed>  $values
     * @return $this
     */
    public function requireVarsMatchValuesForEnvironment(string $environment, array $values): self
    {
        $this->environmentSpecificVarsWithValues ??= collect();

        if (! $this->environmentSpecificVarsWithValues->has($environment)) {
            $this->environmentSpecificVarsWithValues->put($environment, collect($values));
        }

        return $this;
    }

    /**
     * Require the given variable names to be set in the given environments.
     *
     * @param  array<int,string>  $environments
     * @param  array<int,string>  $names
     * @return $this
     */
    public function requireVarsForEnvironments(array $environments, array $names): self
    {
        collect($environments)
            ->each(
                fn (string $environment) => $this->requireVarsForEnvironment($environment, $names)
            );

        return $this;
    }

    /**
     * Require the given variable names to be set, in the given environments, to the values supplied.
     *
     * @param  array<int,string>  $environments
     * @param  array<string,mixed>  $values
     * @return $this
     */
    public function requireVarsMatchValuesForEnvironments(array $environments, array $values): self
    {
        collect($environments)
            ->each(
                fn (string $environment) => $this->requireVarsMatchValuesForEnvironment($environment, $values)
            );

        return $this;
    }

    /**
     * Given a Collection of $vars names, check which of them are not set (in the current environment)
     * and return the list of names as a Collection.
     *
     * @param  Collection<int,string>  $vars
     * @return Collection<int,string>
     */
    protected function missingVars(Collection $vars): Collection
    {
        $missingVars = Collection::empty();

        $vars->each(function (string $name) use ($missingVars) {
            $value = env($name);
            if (! $value) {
                $missingVars->push($name);
            }
        });

        return $missingVars;
    }

    /**
     * @param  Collection<string,mixed>  $requiredVarsWithValues
     */
    protected function checkRequiredVarsWithValues(Collection $requiredVarsWithValues): CheckResultDto
    {
        $failingVarNames = collect();
        $failingVarMessages = collect();

        $requiredVarsWithValues->each(function ($expectedValue, $name) use ($failingVarNames, $failingVarMessages) {
            $actualValue = env($name);

            if ($expectedValue !== $actualValue) {
                $failingVarNames->push($name);
                $failingVarMessages->push(trans('health-env-vars::translations.var_not_matching_value', [
                    'name' => $name,
                    'expected' => self::displayableValueOf($expectedValue),
                    'actual' => self::displayableValueOf($actualValue),
                ]));
            }
        });

        if ($failingVarNames->isEmpty()) {
            return CheckResultDto::ok();
        }

        return CheckResultDto::error(
            meta: $failingVarNames->toArray(),
            summary: trans('health-env-vars::translations.vars_not_matching_values'),
            message: trans('health-env-vars::translations.vars_not_matching_values_list', [
                'list' => $failingVarMessages->implode('; '),
            ])
        );
    }

    public static function displayableValueOf(mixed $var): mixed
    {
        if (is_bool($var)) {
            return var_export($var, true);
        }

        if (is_string($var)) {
            return '"'.$var.'"';
        }

        return $var;
    }
}
