<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis;

use Illuminate\Support\Composer as BaseComposer;

use function array_filter;
use function array_map;
use function array_merge;
use function explode;

class Composer extends BaseComposer
{
    public function installDryRun(?string $options = null): string
    {
        $composer = $this->findComposer();

        $command = array_merge(
            $composer,
            ['install', '--dry-run'],
            array_filter(array_map('trim', explode(' ', $options))),
        );

        $process = $this->getProcess($command);
        $process->run();

        return $process->getOutput() . $process->getErrorOutput();
    }
}
