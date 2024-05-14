<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis\Tests;

use BeyondCode\SelfDiagnosis\Checks\DirectoriesHaveCorrectPermissions;
use Illuminate\Filesystem\Filesystem;
use Mockery;
use Orchestra\Testbench\TestCase;

class DirectoriesHaveCorrectPermissionsTest extends TestCase
{
    /** @test */
    public function it_checks_if_directories_are_writable()
    {
        $config = [
            'directories' => [
                storage_path(),
                base_path('bootstrap/cache'),
            ],
        ];

        $filesystem = Mockery::mock(Filesystem::class);

        $filesystem->shouldReceive('isWritable')
            ->andReturn(false);

        $check = new DirectoriesHaveCorrectPermissions($filesystem);

        $this->assertFalse($check->check($config));


        $filesystem = Mockery::mock(Filesystem::class);

        $filesystem->shouldReceive('isWritable')
            ->andReturn(true);

        $check = new DirectoriesHaveCorrectPermissions($filesystem);

        $this->assertTrue($check->check($config));
    }
}
