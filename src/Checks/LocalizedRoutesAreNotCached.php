<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis\Checks;

use InvalidArgumentException;

use function app;
use function file_exists;
use function substr;

class LocalizedRoutesAreNotCached implements Check
{
    /**
     * The name of the check.
     *
     * @param array $config
     * @return string
     */
    public function name(array $config): string
    {
        return trans('self-diagnosis::checks.localized_routes_are_not_cached.name');
    }

    /**
     * Perform the actual verification of this check.
     *
     * @param array $config
     * @return bool
     */
    public function check(array $config): bool
    {
        if (! app()->bound('laravellocalization')) {
            throw new InvalidArgumentException('LaravelLocalization class does not bounded');
        }

        /** @var \Mcamara\LaravelLocalization\LaravelLocalization $laravelLocalization */
        $laravelLocalization = app('laravellocalization');
        $allLocales = $laravelLocalization->getSupportedLanguagesKeys();

        foreach ($allLocales as $locale) {
            $file = $this->makeLocaleRoutesPath($locale);
            if (file_exists($file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The error message to display in case the check does not pass.
     *
     * @param array $config
     * @return string
     */
    public function message(array $config): string
    {
        return trans('self-diagnosis::checks.localized_routes_are_not_cached.message');
    }

    protected function makeLocaleRoutesPath($locale = '')
    {
        $path = app()->getCachedRoutesPath();

        if (! $locale) {
            return $path;
        }

        return substr($path, 0, -4) . '_' . $locale . '.php';
    }
}
