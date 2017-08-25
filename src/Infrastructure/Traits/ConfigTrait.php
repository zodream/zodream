<?php
namespace Zodream\Infrastructure\Traits;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/9/1
 * Time: 16:39
 */
use Zodream\Service\Config;
trait ConfigTrait {

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
        $configs = Config::getValue($this->configKey, $default);
        if (is_array($configs)) {
            $this->setConfigs($configs);
        }
    }
}