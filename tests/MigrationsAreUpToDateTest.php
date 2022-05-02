<?php

namespace BeyondCode\SelfDiagnosis\Tests;

use Illuminate\Foundation\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use BeyondCode\SelfDiagnosis\Checks\MigrationsAreUpToDate;
use Illuminate\Contracts\Console\Kernel as KernelContract;

class MigrationsAreUpToDateTest extends TestCase
{
    protected function resolveApplicationConsoleKernel($app)
    {
        $app->singleton(KernelContract::class, Kernel::class);
    }

    /** @test */
    public function it_detects_that_migrations_are_up_to_date()
    {
        $check = new MigrationsAreUpToDate();

        Artisan::shouldReceive('call');

        Artisan::shouldReceive('output')
            ->andReturn('Nothing to migrate.');

        $this->assertTrue($check->check([]));
    }

    /** @test */
    public function it_detects_that_migrations_need_to_run()
    {
        $check = new MigrationsAreUpToDate();

        Artisan::shouldReceive('call');

        Artisan::shouldReceive('output')
            ->andReturn('CREATE TABLE foo');

        $this->assertFalse($check->check([]));
    }
}
