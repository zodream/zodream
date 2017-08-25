<?php
namespace Zodream\Domain\ThirdParty\OAuth;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/10
 * Time: 14:34
 */
use Zodream\Domain\ThirdParty\ThirdParty;
use Zodream\Service\Factory;
use Zodream\Infrastructure\ObjectExpand\StringExpand;
use Zodream\Infrastructure\Http\Request;

/**
 * Class BaseOAuth
 * @package Zodream\Domain\ThirdParty\OAuth
 *
 * @property string $identity
 * @property string $username
 * @property string $sex
 * @property string $avatar
 */
abstract class BaseOAuth extends ThirdParty  {

    protected $baseUrl = '';

    public function getBaseUrl() {
        return $this->baseUrl;
    }

    public function getMap($name) {
        $map = parent::getMap($name);
        $baseUrl = $this->getBaseUrl();
        if (empty($baseUrl)) {
            return $map;
        }
        if (is_array($map[0])) {
            $map[0][0] = $baseUrl.$map[0][0];
        } else {
            $map[0] = $baseUrl.$map[0];
        }
        return $map;
    }

    public function callback() {
        Factory::log()
            ->info(strtoupper($this->configKey).' CALLBACK: '.var_export($_GET, true));
        $state = Request::get('state');
        if (empty($state) || $state != Factory::session()->get('state')) {
            return false;
        }
        $code = Request::get('code');
        if (empty($code)) {
            return false;
        }
        $this->set('code', $code);
        return true;
    }

    /**
     * 返回重定向到登录页面的链接
     */
    public function login() {
        $state = StringExpand::randomNumber(7);
        Factory::session()->set('state', $state);
        $this->set('state', $state);
        return $this->getUrl('login');
    }

    /**
     * 获取用户信息
     * @return array
     */
    public abstract function getInfo();
}