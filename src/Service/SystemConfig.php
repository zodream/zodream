<?php
declare(strict_types=1);
namespace Zodream\Service;

use ArrayAccess;
use Zodream\Disk\File;
use Zodream\Helpers\Arr;
use Zodream\Infrastructure\Contracts\Config\Repository;

class SystemConfig implements ArrayAccess, Repository {

    /**
     * All of the configuration items.
     *
     * @var array
     */
    protected array $items = [];

    protected string $folder = 'Service/config';

    /**
     * Create a new configuration repository.
     *
     * @param  array  $items
     * @return void
     */
    public function __construct(array $items = []) {
        $this->items = $items;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }

    /**
     * Get the specified configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string|array $key, mixed $default = null): mixed
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }
        $this->loadFile($key);
        $res = Arr::get($this->items, $key, $default);
        if (!is_array($default)) {
            return $res;
        }
        if (empty($res)) {
            return $default;
        }
        return array_merge($default, (array)$res);
    }

    /**
     * Get many configuration values.
     *
     * @param  array  $keys
     * @return array
     */
    public function getMany(array $keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }
            $this->loadFile($key);
            $config[$key] = Arr::get($this->items, $key, $default);
        }

        return $config;
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set(array|string $key, mixed $value = null): void
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set($this->items, $key, $value);
        }
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend(string $key, mixed $value): void
    {
        $array = $this->get($key);

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push(string $key, mixed $value): void
    {
        $array = $this->get($key);

        $array[] = $value;

        $this->set($key, $array);
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists(mixed $key): bool
    {
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet(mixed $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Unset a configuration option.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset(mixed $key): void
    {
        $this->set($key, null);
    }

    public function configPath(string $name): File {
        return app_path(sprintf('%s/%s.php', $this->folder, $name));
    }

    protected function loadFile(string $key) {
        if (empty($key)) {
            return;
        }
        $name = explode('.', trim($key, '.'), 2)[0];
        if (isset($this->items[$name])) {
            return;
        }
        $file = $this->configPath($name);
        if (!$file->exist()) {
            $this->items[$name] = [];
            return;
        }
        $this->items[$name] = require (string)$file;
    }
}