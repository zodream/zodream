<?php
namespace Zodream\Infrastructure\Base;

use Zodream\Disk\Directory;
use Zodream\Disk\File;
use Zodream\Helpers\Arr;
use Zodream\Service\Factory;

class Config extends MagicObject {

    protected $cache_data = [];

    /**
     * @var Directory
     */
    protected $directory;

    public function setDirectory($value = null) {
        if (!is_dir($value) && defined('APP_DIR') && is_dir(APP_DIR)) {
            $value = 'Service/config';
        }
        $this->directory = Factory::root()->directory($value);
        return $this;
    }

    /**
     * 支持与默认合并参数
     * @param string $key
     * @param mixed $default
     * @return array|null|string
     */
    public function get($key = null, $default = null) {
        if (empty($default) && isset($this->cache_data[$key])) {
            return $this->cache_data[$key];
        }
        $args = parent::getAttribute($key, $default);
        if (empty($default)) {
            return $this->cache_data[$key] = $args;
        }
        if (!is_array($default)) {
            return $args;
        }
        if (empty($args)) {
            return $default;
        }
        return array_merge($default, (array)$args);
    }

    /**
     * 支持根据键合并数组
     * @param array|string $key
     * @param mixed $value
     * @return Config
     */
    public function set($key, $value = null) {
        $this->cache_data = [];
        if (!is_array($key) && $this->hasAttribute($key) && is_array($value)) {
            $this->__attributes[$key] = array_merge((array)$this->__attributes[$key], $value);
            return $this;
        }
        parent::setAttribute($key, $value);
        return $this;
    }

    /**
     * @return Directory
     */
    public function getDirectory() {
        if (!$this->directory instanceof Directory) {
            $this->setDirectory();
        }
        return $this->directory;
    }

    public function add($key, $value = null) {
        if (is_null($value)) {
            $value = $key;
        }
        if (is_string($value)) {
            $value = $this->getConfigByFile($value);
        }
        if (empty($value)) {
            return $this;
        }
        if ($this->hasAttribute($key)) {
            $value = array_merge($this->get($key), $value);
        }
        return $this->set($key, $value);
    }

    public function mergeFiles($args) {
        if (!is_array($args)) {
            $args = func_get_args();
        }
        $data = [];
        if ($this->has()) {
            $data[] = $this->get();
        }
        foreach ($args as $arg) {
            $arg = $this->getConfigByFile($arg);
            if (!empty($arg)) {
                $data[] = $arg;
            }
        }
        $this->__attributes = call_user_func_array('Zodream\Helpers\Arr::merge2D', $data);
        return $this;
    }

    /**
     * @param $file
     * @return array
     */
    public function getConfigByFile($file) {
        if (!$file instanceof File) {
            $file = $this->getRealFile($file);
        }
        if (!$file->exist()) {
            return [];
        }
        return include (string)$file;
    }

    /**
     * @param string $name
     * @return File
     */
    protected function getRealFile($name) {
        if (!preg_match('/^\w+$/', $name, $m) && is_file($name)) {
            return new File($name);
        }
        return $this->getDirectory()->file($name.'.php');
    }

    /**
     * 追加
     * @param $name
     * @param array $configs
     */
    public function append($name, array $configs) {
        $data = $this->getConfigByFile($name);
        $this->save($name, Arr::merge2D($data, $configs));
    }

    /**
     * 保存
     * @param $name
     * @param array $configs
     */
    public function save($name, array $configs) {
        $content = [
            '<?php',
            sprintf('return %s;', var_export($configs, true))
        ];
        $this->getDirectory()->file($name.'.php')
            ->write(implode(PHP_EOL, $content));
    }

    /**
     * @param string $key
     * @return object
     */
    public function createObject($key) {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(
                __('Config error')
            );
        }
        $data = $this->get($key);
        $driver = $data['driver'];
        unset($data['driver']);
        return new $driver($data);
    }

}