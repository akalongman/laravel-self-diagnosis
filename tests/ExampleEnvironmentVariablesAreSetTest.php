<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis\Tests;

use BeyondCode\SelfDiagnosis\Checks\ExampleEnvironmentVariablesAreSet;
use BeyondCode\SelfDiagnosis\SelfDiagnosisServiceProvider;
use Orchestra\Testbench\TestCase;

use const PHP_EOL;

class ExampleEnvironmentVariablesAreSetTest extends TestCase
{
    public function getPackageProviders($app)
    {
        return [
            SelfDiagnosisServiceProvider::class,
        ];
    }

    /** @test */
    public function it_checks_if_example_env_variables_are_set_in_env_file()
    {
        $this->app->setBasePath(__DIR__ . '/fixtures');

        $check = new ExampleEnvironmentVariablesAreSet();

        $this->assertFalse($check->check([]));
        $this->assertSame('These environment variables are missing in your .env file, but are defined in your .env.example:' . PHP_EOL . 'KEY_TWO', $check->message([]));
    }
}
