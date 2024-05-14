<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis\Checks;

use Dotenv\Dotenv;
use Dotenv\Environment\FactoryInterface;
use Illuminate\Support\Collection;

use function interface_exists;
use function method_exists;

use const PHP_EOL;

class ExampleEnvironmentVariablesAreSet implements Check
{
    /** @var \Illuminate\Support\Collection */
    private $envVariables;

    /**
     * The name of the check.
     *
     * @param array $config
     * @return string
     */
    public function name(array $config): string
    {
        return trans('self-diagnosis::checks.example_environment_variables_are_set.name');
    }

    /**
     * Perform the actual verification of this check.
     *
     * @param array $config
     * @return bool
     */
    public function check(array $config): bool
    {
        if (method_exists(Dotenv::class, 'createUnsafeImmutable')) {
            return $this->checkForDotEnvV5();
        }
        if (method_exists(Dotenv::class, 'createImmutable')) {
            return $this->checkForDotEnvV4();
        }

        if (interface_exists(FactoryInterface::class)) {
            $examples = Dotenv::create(base_path(), '.env.example');
            $actual = Dotenv::create(base_path(), '.env');
        } else {
            $examples = new Dotenv(base_path(), '.env.example');
            $actual = new Dotenv(base_path(), '.env');
        }

        $examples->safeLoad();
        $actual->safeLoad();

        $this->envVariables = Collection::make($examples->getEnvironmentVariableNames())
            ->diff($actual->getEnvironmentVariableNames());

        return $this->envVariables->isEmpty();
    }

    /**
     * The error message to display in case the check does not pass.
     *
     * @param array $config
     * @return string
     */
    public function message(array $config): string
    {
        return trans('self-diagnosis::checks.example_environment_variables_are_set.message', [
            'variables' => $this->envVariables->implode(PHP_EOL),
        ]);
    }

    /**
     * Perform the verification of this check for DotEnv v4.
     *
     * @return bool
     */
    private function checkForDotEnvV4(): bool
    {
        $examples = Dotenv::createImmutable(base_path(), '.env.example');
        $actual = Dotenv::createImmutable(base_path(), '.env');

        $this->envVariables = Collection::make($examples->safeLoad())
            ->diffKeys($actual->safeLoad())
            ->keys();

        return $this->envVariables->isEmpty();
    }

    /**
     * Perform the verification of this check for DotEnv v5.
     *
     * @return bool
     */
    private function checkForDotEnvV5(): bool
    {
        $examples = Dotenv::createMutable(base_path(), '.env.example');
        $actual = Dotenv::createMutable(base_path(), '.env');

        $this->envVariables = Collection::make($examples->safeLoad())
            ->diffKeys($actual->safeLoad())
            ->keys();

        return $this->envVariables->isEmpty();
    }
}
