<?php
namespace Zodream\Infrastructure\Base;

use JsonSerializable;
use Zodream\Disk\Directory;
use Zodream\Disk\File;
use Zodream\Helpers\Json;
use Zodream\Infrastructure\Interfaces\JsonAble;
use Zodream\Service\Factory;

class Config extends MagicObject implements JsonAble, JsonSerializable {

    /**
     * @var Directory
     */
    protected $directory;

    public function setDirectory($value = null) {
        if (!is_dir($value) && defined('APP_DIR') && is_dir(APP_DIR)) {
            $value = 'Service/config'; ;
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
        $args = parent::get($key, $default);
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
     * @return $this
     */
    public function set($key, $value = null) {
        if (!is_array($key) && $this->has($key) && is_array($value)) {
            $this->_data[$key] = array_merge((array)$this->_data[$key], $value);
            return $this;
        }
        return parent::set($key, $value);
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
            $value = $this->getDataByFile($value);
        }
        if (empty($value)) {
            return $this;
        }
        if ($this->has($key)) {
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
        $this->_data = call_user_func_array('Zodream\Helpers\Arr::merge2D', $data);
        return $this;
    }

    /**
     * @param $file
     * @return array
     */
    protected function getConfigByFile($file) {
        $file = $this->getRealFile($file);
        if (!$file->exist()) {
            return [];
        }
        return include (string)$file;
    }

    /**
     * @param $name
     * @return File
     */
    protected function getRealFile($name) {
        if (!preg_match('/^\w+$/', $name, $m) && is_file($name)) {
            return new File($name);
        }
        return $this->getDirectory()->file($name.'.php');
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = JSON_UNESCAPED_UNICODE) {
        return Json::encode($this->toArray(), $options);
    }

    public function save($name = null) {
        if (empty($name)) {
            $name = APP_MODULE;
        }
        //$generate = new Generate();
        //return $generate->setReplace(true)->makeConfig(static::getValue(), $name);
    }

    /**
     * @param string $key
     * @return object
     */
    public function createObject($key) {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException('CONFIG ERROR!');
        }
        $data = $this->get($key);
        $driver = $data['driver'];
        unset($data['driver']);
        return new $driver($data);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize() {
        return $this->toArray();
    }

}