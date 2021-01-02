<?php
namespace Zodream\Infrastructure\Concerns;


trait ConfigTrait {
//    /**
//     * CONFIGS
//     * @var array
//     */
//    protected $configs = [];
//    /**
//     * KEY IN CONFIG
//     * @var string
//     */
//    protected $configKey = 'app';

    /**
     * SET CONFIGS
     * @param array $args
     * @return $this
     */
    public function setConfigs(array $args) {
        $this->configs = array_merge($this->configs, $args);
        return $this;
    }

    public function loadConfigs($default = []) {
        if (empty($this->configKey)) {
            return;
        }
        $configs = config($this->configKey, $default);
        if (is_array($configs)) {
            $this->setConfigs($configs);
        }
    }
}