<?php
namespace Zodream\Domain\ThirdParty\OAuth;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/21
 * Time: 11:08
 */
class DouBan extends BaseOAuth {

    protected $configKey = 'douban';

    protected $apiMap = [
        'login' => [
            'https://www.douban.com/service/auth2/auth',
            [
                '#client_id',
                '#redirect_uri',
                'response_type' => 'code',
                'scope',
                'state'
            ]
        ],
        'access' => [
            'https://www.douban.com/service/auth2/token',
            [
                '#client_id',
                '#client_secret',
                '#redirect_uri',
                'grant_type' => 'authorization_code',
                '#code'
            ],
            'post'
        ],
        'refresh' => [
            'https://www.douban.com/service/auth2/token',
            [
                '#client_id',
                '#client_secret',
                '#redirect_uri',
                'grant_type' => 'refresh_token',
                '#refresh_token'
            ],
            'post'
        ],
        'info' => [
            'https://api.douban.com/v2/user/~me'
        ]
    ];

    public function callback() {
        if (parent::callback() === false) {
            return false;
        }
        $access = $this->getJson('access');
        if (!is_array($access) || !array_key_exists('douban_user_id', $access)) {
            return false;
        }
        $access['identity'] = $access['douban_user_id'];
        $this->set($access);
        return $access;
    }

    /**
     * 获取用户信息
     * @return array|false
     */
    public function getInfo() {
        $user = $this->getJson('info');
        if (!is_array($user) || !array_key_exists('name', $user)) {
            return false;
        }
        $user['username'] = $user['name'];
        $user['sex'] = '女';
        return $user;
    }
}