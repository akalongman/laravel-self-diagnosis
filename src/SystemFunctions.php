<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis;

use function ini_get;
use function is_callable;
use function shell_exec;
use function stripos;
use function strtoupper;
use function substr;

use const PHP_OS;

/**
 * Proxy class for system functions.
 *
 * Class SystemFunctions
 */
class SystemFunctions
{
    /**
     * Performs a shell_exec call. Acts as proxy.
     *
     * @param string $command
     * @return string|null
     */
    public function callShellExec(string $command): ?string
    {
        return shell_exec($command);
    }

    /**
     * Checks if a function is defined and not disabled.
     *
     * @param string $function
     * @return bool
     */
    public function isFunctionAvailable(string $function): bool
    {
        return is_callable($function) && stripos(ini_get('disable_functions'), $function) === false;
    }

    /**
     * Checks if we are running on a windows operating system.
     *
     * @return bool
     */
    public function isWindowsOperatingSystem(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}
