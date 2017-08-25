<?php
namespace Zodream\Domain\ThirdParty\OAuth;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/21
 * Time: 15:52
 */
class TaoBao extends BaseOAuth {

    protected $configKey = 'taobao';

    protected $apiMap = array(
        'login' => array(
            'https://oauth.taobao.com/authorize',
            array(
                '#client_id',
                '#redirect_uri',
                'response_type' => 'code',
                'scope',
                '#view',
                'state'
            )
        ),
        'access' => array(
            'https://oauth.taobao.com/token',
            array(
                '#client_id',
                '#client_secret',
                '#code',
                '#redirect_uri',
                '#view',
                'grant_type' => 'authorization_code'
            )
        )
    );

    public function callback() {
        parent::callback();
        /**
         * access_token

        用户授权令牌，等价于Sessionkey

        token_type

        授权令牌类型，暂做保留参数备用

        expires_in

        授权令牌有效期，以秒为单位

        refresh_token

        刷新令牌，当授权令牌过期时，可以刷新access_token，如果有获取权限则返回

        re_expires_in

        刷新令牌的有效期

        hra_expires_in

        高危API有效期（短授权相关）

        taobao_user_id

        用户ID（子账号相关）

        taobao_user_nick

        用户nick

        taobao_sub_user_id

        子账号用户ID

        taobao_sub_user_nick

        子账号用户nick

        mobile_token

        无线端的ssid（对应于view=wap）
         */
        $access = $this->getJson('access');
        $this->set($access);
        return $access;
    }

    /**
     * 获取用户信息
     * @return array
     */
    public function getInfo() {
        return $this->get();
    }
}