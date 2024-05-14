<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis\Checks;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use function array_keys;
use function array_unique;
use function extension_loaded;
use function json_decode;

use const PHP_EOL;

class PhpExtensionsAreInstalled implements Check
{
    private const EXT = 'ext-';

    /** @var \Illuminate\Filesystem\Filesystem */
    private $filesystem;

    /** @var \Illuminate\Support\Collection */
    private $extensions;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * The name of the check.
     *
     * @param array $config
     * @return string
     */
    public function name(array $config): string
    {
        return trans('self-diagnosis::checks.php_extensions_are_installed.name');
    }

    /**
     * The error message to display in case the check does not pass.
     *
     * @param array $config
     * @return string
     */
    public function message(array $config): string
    {
        return trans('self-diagnosis::checks.php_extensions_are_installed.message', [
            'extensions' => $this->extensions->implode(PHP_EOL),
        ]);
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
        if (Arr::get($config, 'include_composer_extensions', false)) {
            $this->extensions = $this->extensions->merge($this->getExtensionsRequiredInComposerFile());
            $this->extensions = $this->extensions->unique();
        }
        $this->extensions = $this->extensions->reject(static function ($ext) {
            return extension_loaded($ext);
        });

        return $this->extensions->isEmpty();
    }

    /**
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getExtensionsRequiredInComposerFile()
    {
        $installedPackages = json_decode($this->filesystem->get(base_path('vendor/composer/installed.json')), true);

        $extensions = [];
        foreach ($installedPackages as $installedPackage) {
            $filtered = Arr::where(array_keys(Arr::get($installedPackage, 'require', [])), static function ($value, $key) {
                return Str::startsWith($value, self::EXT);
            });
            foreach ($filtered as $extension) {
                $extensions[] = Str::replaceFirst(self::EXT, '', $extension);
            }
        }

        return array_unique($extensions);
    }
}
