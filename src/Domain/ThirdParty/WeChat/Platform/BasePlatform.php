<?php
namespace Zodream\Domain\ThirdParty\WeChat\Platform;

use Zodream\Domain\ThirdParty\WeChat\BaseWeChat;

abstract class BasePlatform extends BaseWeChat {
    protected $configKey = 'wechat.platform';

    public function __construct(array $config = array()) {
        parent::__construct($config);
        $this->set('component_appid', $this->get('component_appid'));
        $this->set('component_appsecret', $this->get('component_appsecret'));
    }

    protected function getData(array $keys, array $args) {
        if ((in_array('#component_access_token', $keys)
                || in_array('component_access_token', $keys))
            && (!$this->has('component_access_token')
                || !array_key_exists('component_access_token', $args))) {
            $args['component_access_token'] = (new Manage())->getToken();
        }
        return parent::getData($keys, $args);
    }
}