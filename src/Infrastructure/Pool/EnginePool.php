<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Pool;

use Zodream\Infrastructure\Base\ConfigObject;

abstract class EnginePool extends ConfigObject {
    protected array $engines = [];

    protected string|int $currentName = '__default__';

    protected string $defaultDriver;

    public function __construct() {
        $this->loadConfigs();
        $this->getCurrentName();
    }

    /**
     * ADD 2D ARRAY
     * @param array $args
     * @return $this
     */
    public function setConfigs(array $args) {
        if (!is_array(current($args))) {
            $args = [
                $this->currentName => $args
            ];
        }
        foreach ($args as $key => $item) {
            if (array_key_exists($key, $this->configs)) {
                $this->configs[$key] = array_merge($this->configs[$key], $item);
            } else {
                $this->configs[$key] = $item;
            }
        }
        return $this;
    }

    /**
     * @param string|array|mixed $name
     * @param array|mixed|null $configs
     * @return mixed
     * @throws \Exception
     */
    public function addEngine(array|string|int $name, array|null $configs = null) {
        if (!is_string($name) && !is_numeric($name)) {
            $configs = $name;
            $name = $this->currentName;
        }
        if (array_key_exists($name, $this->engines) && method_exists($this->engines[$name], 'close')) {
            $this->engines[$name]->close();
        }
        if (is_object($configs)) {
            return $this->engines[$name] = $configs;
        }
        if (empty($configs) || !isset($configs['driver']) || !class_exists($configs['driver'])) {
            $configs['driver'] = $this->defaultDriver;
        }
        $class = $configs['driver'];
        $this->engines[$name] = new $class($configs);
        $this->onEngineInitialized($this->engines[$name]);
        return $this->engines[$name];
    }

    /**
     * GET DATABASE ENGINE
     * @param string|int $name
     * @return mixed
     * @throws \Exception
     */
    public function getEngine(string|int $name = '') {
        if ($name === '') {
            $name = $this->getCurrentName();
        }
        if (array_key_exists($name, $this->engines)) {
            return $this->engines[$name];
        }
        if (!$this->hasConfig($name)) {
            throw new \InvalidArgumentException(
                sprintf(
                    __('%s DOES NOT HAVE CONFIG!')
                    , $name)
            );
        }
        $engine = $this->addEngine($name, $this->getConfig($name));
        $this->onEngineChanged($engine);
        return $engine;
    }

    protected function onEngineInitialized(mixed $engine) {}
    protected function onEngineChanged(mixed $engine) {}

    public function getCurrentName() {
        if (!array_key_exists($this->currentName, $this->configs)) {
            $this->currentName = key($this->configs);
        }
        return $this->currentName;
    }

    public function getConfig(string|int|null $name = null) {
        if (is_null($name)) {
            $name = $this->getCurrentName();
        }
        return array_key_exists($name, $this->configs) ? $this->configs[$name] : [];
    }

    public function hasConfig(string|int|null $name = null) {
        if (is_null($name)) {
            return empty($this->configs);
        }
        return array_key_exists($name, $this->configs);
    }
}