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

    /** @var Collection<int,string> */
    protected Collection $environmentSpecificVars;

    /**
     * Run the check and return the Result
     */
    public function run(): Result
    {
        $this->requiredVars ??= Collection::empty();
        $this->environmentSpecificVars ??= Collection::empty();

        $result = Result::make();

        // Check all provided variable names match .env variables with non-empty value
        $missingVars = $this->missingVars($this->requiredVars);

        if ($missingVars->count() > 0) {
            return $result->meta($missingVars->toArray())
                ->shortSummary(trans('health-env-vars::translations.not_every_var_has_been_set'))
                ->failed(
                    trans('health-env-vars::translations.missing_vars_list', [
                        'list' => $missingVars->implode(','),
                    ])
                );
        }

        /** @var string $currentEnvironment */
        $currentEnvironment = App::environment();
        // Same for environment specific vars (if any), returning different error messages
        $missingVars = $this->missingVars(
            $this->environmentSpecificVars->get($currentEnvironment, Collection::empty())
        );

        if ($missingVars->count() > 0) {
            return $result->meta($missingVars->toArray())
                ->shortSummary(trans('health-env-vars::translations.not_every_var_has_been_set'))
                ->failed(
                    trans('health-env-vars::translations.missing_vars_list_in_environment', [
                        'environment' => App::environment(),
                        'list' => $missingVars->implode(','),
                    ])
                );
        }

        return $result->ok()
            ->shortSummary(trans('health-env-vars::translations.every_var_has_been_set'));
    }

    /**
     * Require the given variable names to be set (no matter in which environment)
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
     * Require the given variable names to be set in the given environment
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
     * Require the given variable names to be set in the given environments
     *
     * @param  array<int,string>  $environments
     * @param  array<int,string>  $names
     * @return $this
     */
    public function requireVarsForEnvironments(array $environments, array $names): self
    {
        collect($environments)->each(fn ($environment) => $this->requireVarsForEnvironment($environment, $names));

        return $this;
    }

    /**
     * Given a Collection of $vars names, check which of them are not set (in the current environment)
     * and return the list of names as a Collection
     *
     * @param  Collection<int,string>  $vars
     * @return Collection<int,string>
     */
    protected function missingVars(Collection $vars): Collection
    {
        $missingVars = Collection::empty();

        $vars->each(function (string $name) use ($missingVars) {
            $value = getenv($name);
            if (! $value) {
                $missingVars->push($name);
            }
        });

        return $missingVars;
    }
}
