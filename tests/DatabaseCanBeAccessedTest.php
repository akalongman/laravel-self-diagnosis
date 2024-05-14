<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis\Tests;

use BeyondCode\SelfDiagnosis\Checks\DatabaseCanBeAccessed;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Mockery;
use Orchestra\Testbench\TestCase;

class DatabaseCanBeAccessedTest extends TestCase
{
    /** @test */
    public function it_checks_db_access()
    {
        $check = app(DatabaseCanBeAccessed::class);
        $this->assertFalse($check->check([]));

        $mock = Mockery::mock(Connection::class);
        $mock->shouldReceive('getPdo');

        DB::shouldReceive('connection')->andReturn($mock);

        $this->assertTrue($check->check([]));
    }
}
