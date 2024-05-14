<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis\Checks;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

use function extension_loaded;

use const PHP_EOL;

class PhpExtensionsAreDisabled implements Check
{
    /** @var \Illuminate\Support\Collection */
    private $extensions;

    /**
     * The name of the check.
     *
     * @param array $config
     * @return string
     */
    public function name(array $config): string
    {
        return trans('self-diagnosis::checks.php_extensions_are_disabled.name');
    }

    /**
     * Perform the actual verification of this check.
     *
     * @param array $config
     * @return bool
     */
    public function check(array $config): bool
    {
        $this->extensions = Collection::make(Arr::get($config, 'extensions', []));
        $this->extensions = $this->extensions->reject(static function ($ext) {
            return extension_loaded($ext) === false;
        });

        return $this->extensions->isEmpty();
    }

    /**
     * The error message to display in case the check does not pass.
     *
     * @param array $config
     * @return string
     */
    public function message(array $config): string
    {
        return trans('self-diagnosis::checks.php_extensions_are_disabled.message', [
            'extensions' => $this->extensions->implode(PHP_EOL),
        ]);
    }
}
