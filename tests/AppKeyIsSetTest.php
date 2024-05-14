<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis\Tests;

use BeyondCode\SelfDiagnosis\Checks\AppKeyIsSet;
use Orchestra\Testbench\TestCase;

class AppKeyIsSetTest extends TestCase
{
    /** @test */
    public function it_checks_app_key_existance()
    {
        $check = app(AppKeyIsSet::class);
        $this->assertFalse($check->check([]));

        $this->app['config']->set('app.key', 'foo');

        $this->assertTrue($check->check([]));
    }
}
