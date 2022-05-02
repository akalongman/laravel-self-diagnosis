<?php

namespace BeyondCode\SelfDiagnosis\Tests;

use BeyondCode\SelfDiagnosis\Checks\HorizonIsRunning;
use Illuminate\Contracts\Redis\Factory;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Repositories\RedisMasterSupervisorRepository;
use Orchestra\Testbench\TestCase;
use stdClass;

use function app;

class HorizonIsRunningTest extends TestCase
{
    /** @test */
    public function it_succeeds_when_horizon_is_running()
    {
        $check = new HorizonIsRunning($this->getMasterSupervisorRepository('running'));

        $this->assertTrue($check->check([]));
    }

    /** @test */
    public function is_fails_when_horizon_is_not_running()
    {
        $check = new HorizonIsRunning($this->getMasterSupervisorRepository('paused'));

        $this->assertFalse($check->check([]));
    }

    private function getMasterSupervisorRepository(string $status): MasterSupervisorRepository
    {
        $mock = $this->getMockBuilder(RedisMasterSupervisorRepository::class)
            ->onlyMethods([
                'all',
            ])
            ->setConstructorArgs([app(Factory::class)])
            ->getMock();

        $class = new stdClass();
        $class->status = $status;

        $mock->method('all')->willReturn([$class]);

        return $mock;
    }
}
