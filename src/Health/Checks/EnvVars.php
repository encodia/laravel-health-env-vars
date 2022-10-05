<?php

namespace Encodia\Health\Checks;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class EnvVars extends Check
{
    protected Collection $requiredVars;
    protected Collection $environmentSpecificVars;

    public function run(): Result
    {
        $this->requiredVars ??= Collection::make();
        $this->environmentSpecificVars ??= Collection::make();

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

        // Same for environment specific vars (if any), returning different error messages
        $missingVars = $this->missingVars(
            $this->environmentSpecificVars->get(App::environment(), Collection::make())
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
            ->shortSummary(trans('health-env-vars::translations.every_var_has_been_set'))
            ->notificationMessage(trans('health-env-vars::translations.every_var_has_been_set'));
    }

    public function requireVars(array $names): self
    {
        $this->requiredVars = Collection::make($names);

        return $this;
    }

    public function requireVarsForEnvironment(string $environment, array $names): self
    {
        // This method could be called several times (e.g. for different environments)

        $this->environmentSpecificVars ??= Collection::make();

        if (! $this->environmentSpecificVars->has($environment)) {
            $this->environmentSpecificVars->put($environment, Collection::make($names));
        }

        return $this;
    }

    /**
     * Given a Collection of $vars names, check which of them are not set (in the current environment)
     * and return the list of names as a Collection
     *
     * @param  Collection  $vars
     * @return Collection
     */
    protected function missingVars(Collection $vars): Collection
    {
        $missingVars = Collection::make();

        $vars->each(function (string $name) use ($missingVars) {
            $value = getenv($name);
            if (! $value) {
                $missingVars->push($name);
            }
        });

        return $missingVars;
    }
}
