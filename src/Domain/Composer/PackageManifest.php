<?php
declare(strict_types=1);
namespace Zodream\Domain\Composer;

use Exception;
use Zodream\Disk\Directory;
use Zodream\Disk\File;
use Zodream\Helpers\Json;

class PackageManifest {

    /**
     * The base path.
     *
     * @var Directory
     */
    public $basePath;

    /**
     * The vendor path.
     *
     * @var Directory
     */
    public $vendorPath;

    /**
     * The manifest path.
     *
     * @var File
     */
    public $manifestPath;

    /**
     * The loaded manifest array.
     *
     * @var array
     */
    public $manifest;

    public $extraName = 'zodream';

    public function __construct(Directory $basePath, File $manifestPath)
    {
        $this->basePath = $basePath;
        $this->manifestPath = $manifestPath;
        $this->vendorPath = $this->basePath->directory('vendor');
    }

    /**
     * Get all of the service provider class names for all packages.
     *
     * @return array
     */
    public function providers(): array
    {
        return $this->config('providers');
    }

    /**
     * Get all of the aliases for all packages.
     *
     * @return array
     */
    public function aliases(): array
    {
        return $this->config('aliases');
    }

    /**
     * Get all of the values for all packages for the given configuration name.
     *
     * @param  string  $key
     * @return array
     */
    public function config(string $key): array
    {
        $data = $this->getManifest();
        if (!isset($data[$key])) {
            return [];
        }
        return array_filter((array)$data[$key], function ($item) {
            return !empty($item);
        });
    }

    /**
     * Get the current package manifest.
     *
     * @return array
     */
    protected function getManifest(): array
    {
        if (! is_null($this->manifest)) {
            return $this->manifest;
        }

        if (!$this->manifestPath->exist()) {
            $this->build();
        }
        if (!$this->manifestPath->exist()) {
            return [];
        }
        return $this->manifest = require (string)$this->manifestPath;
    }

    /**
     * Build the manifest and write it to disk.
     *
     * @return void
     */
    public function build()
    {
        $packages = [];

        $path = $this->vendorPath->file('/composer/installed.json');
        if ($path->exist()) {
            $installed = Json::decode($path->read());

            $packages = $installed['packages'] ?? $installed;
        }

        $ignoreAll = in_array('*', $ignore = $this->packagesToIgnore());

        $items = [];
        foreach ($packages as $package) {
            $name = $this->format($package['name']);
            $configuration = $package['extra'][$this->extraName] ?? [];
            $ignore = array_merge($ignore, $configuration['dont-discover'] ?? []);
            if (empty($configuration) || $ignoreAll) {
                continue;
            }
            $items[$name] = $configuration;
        }
        foreach ($items as $name => $configuration) {
            if (in_array($name, $ignore)) {
                unset($items[$name]);
            }
        }
        $this->write($items);
    }

    /**
     * Format the given package name.
     *
     * @param  string  $package
     * @return string
     */
    protected function format(string $package): string
    {
        return str_replace($this->vendorPath.'/', '', $package);
    }

    /**
     * Get all of the package names that should be ignored.
     *
     * @return array
     */
    protected function packagesToIgnore(): array
    {
        $file = $this->basePath->file('composer.json');
        if (!$file->exist()) {
            return [];
        }

        return Json::decode(
                $file->read()
            )['extra'][$this->extraName]['dont-discover'] ?? [];
    }

    /**
     * Write the given manifest array to disk.
     *
     * @param  array  $manifest
     * @return void
     *
     * @throws \Exception
     */
    protected function write(array $manifest)
    {
        if ($this->manifestPath->exist() && !$this->manifestPath->canWrite()) {
            throw new Exception("The {$this->manifestPath} file must be present and writable.");
        }

        $this->manifestPath->write('<?php return '.var_export($manifest, true).';');
    }
}