<?php
namespace Zodream\Domain\ThirdParty\WeChat\Platform;

use Zodream\Infrastructure\Http\Component\Uri;
use Zodream\Infrastructure\Http\Request;
use Zodream\Service\Factory;

/**
 * 微信第三方平台管理公众号
 * User: zx648
 * Date: 2017/4/5
 * Time: 19:34
 */
class Manage extends BasePlatform {


    protected $apiMap = [
        'login' => [
            'https://mp.weixin.qq.com/cgi-bin/componentloginpage',
            [
                '#component_appid',
                '#pre_auth_code',
                '#redirect_uri'
            ]
        ],
        'token' => [ //json
            'https://api.weixin.qq.com/cgi-bin/component/api_component_token',
            [
                '#component_appid',
                '#component_appsecret',
                '#component_verify_ticket'
            ],
            'POST'
        ],
        'pre_auth_code' => [  //json
            [
                'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode',
                '#component_access_token'
            ],
            '#component_appid',
            'POST'
        ],
        'access_token' => [ //json
            [
                'https://api.weixin.qq.com/cgi-bin/component/api_query_auth',
                '#component_access_token'
            ],
            [
                '#component_appid',
                '#authorization_code' // 在授权通知里接收
            ],
            'POST'
        ],
        'refresh_token' => [
            [
                'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token',
                '#component_access_token'
            ],
            [
                '#component_appid',
                '#authorizer_appid',
                '#authorizer_refresh_token',
            ],
            'POST'
        ],
        'info' => [
            [
                'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info',
                '#component_access_token'
            ],
            [
                '#component_appid',
                '#authorizer_appid',
            ],
            'POST'
        ],
        'getOption' => [
            [
                'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_option',
                '#component_access_token'
            ],
            [
                '#component_appid',
                '#authorizer_appid',
                '#option_name'
            ],
            'POST'
        ],
        'setOption' => [
            [
                'https://api.weixin.qq.com/cgi-bin/component/api_set_authorizer_option',
                '#component_access_token'
            ],
            [
                '#component_appid',
                '#authorizer_appid',
                '#option_name',
                '#option_value'
            ],
            'POST'
        ],
        'clear' => [
            [
                'https://api.weixin.qq.com/cgi-bin/component/clear_quota',
                '#component_access_token'
            ],
            '#component_appid',
            'POST'
        ]
    ];

    /**
     * 2.获取令牌
     * @return mixed
     * @throws \Exception
     */
    public function getToken() {
        $key = 'WeChatThirdToken';
        if (Factory::cache()->has($key)) {
            return Factory::cache()->get($key);
        }
        $args = $this->getJson('token', [
            'component_verify_ticket' => Factory::cache()
                ->get('WeChatThirdComponentVerifyTicket')
        ]);
        if (!is_array($args)) {
            throw new \Exception('HTTP ERROR!');
        }
        if (!array_key_exists('component_access_token', $args)) {
            throw new \Exception(isset($args['errmsg']) ? $args['errmsg'] : 'GET ACCESS TOKEN ERROR!');
        }
        Factory::cache()->set($key, $args['component_access_token'], $args['expires_in']);
        return $args['component_access_token'];
    }

    /**
     * 3.获取预授权码
     * @return mixed
     * @throws \Exception
     */
    public function getPreAuthCode() {
        $key = 'WeChatThirdPreAuthCode';
        if (Factory::cache()->has($key)) {
            return Factory::cache()->get($key);
        }
        $args = $this->getJson('pre_auth_code');
        if (!is_array($args)) {
            throw new \Exception('HTTP ERROR!');
        }
        if (!array_key_exists('pre_auth_code', $args)) {
            throw new \Exception(isset($args['errmsg']) ? $args['errmsg'] : 'GET ACCESS TOKEN ERROR!');
        }
        Factory::cache()->set($key, $args['pre_auth_code'], $args['expires_in']);
        return $args['pre_auth_code'];
    }

    /**
     * 4.进入授权页面
     * @return Uri
     */
    public function login() {
        $this->set('pre_auth_code', $this->getPreAuthCode());
        return $this->getUrl('login');
    }

    public function callback() {
        $code = Request::get('auth_code');
        if (empty($code)) {
            throw new \Exception('AUTH CODE ERROR!');
        }
        Factory::log()->info('WECHAT AUTH CODE: '. $code);
        $this->set('authorization_code', $code);
        return $this->getAccessToken();
    }

    public function getAccessToken() {
        $data = $this->getJson('access_token');
        if (!array_key_exists('authorization_info', $data)) {
            throw new \Exception('ACCESS TOKEN ERROR!');
        }
        $this->set($data['authorization_info']);
        return $data['authorization_info'];
    }

    /**
     *
     * @param $appId 公众号的appid
     * @param $token
     * @return mixed
     * @throws \Exception
     */
    public function refreshAccessToken($appId, $token) {
        $data = $this->getJson('refresh_token', array(
            'authorizer_appid' => $appId,
            'authorizer_refresh_token' => $token
        ));
        if (!array_key_exists('authorizer_access_token', $data)) {
            throw new \Exception('REFRESH ACCESS TOKEN ERROR!');
        }
        $this->set($data);
        return $data;
    }

    /**
     * 获取公众号的信息
     * @param $appId 公众号的appid
     * @return
     * @throws \Exception
     */
    public function getInfo($appId) {
        $data = $this->getJson('info', [
            'authorizer_appid' => $appId
        ]);
        if (!array_key_exists('authorizer_info', $data)) {
            throw new \Exception('INFO ERROR!');
        }
        $this->set($data['authorizer_info']);
        return $data['authorizer_info'];
    }

    public function getOption($appId, $name) {
        return $this->getJson('getOption', [
            'authorizer_appid' => $appId,
            'option_name' => $name
        ]);
    }

    public function setOption($appId, $name, $value) {
        $data = $this->getJson('setOption', [
            'authorizer_appid' => $appId,
            'option_name' => $name,
            'option_value' => $value
        ]);
        return $data['errcode'] === 0;
    }
}