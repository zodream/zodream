<?php
namespace Zodream\Domain\OAuth;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/13
 * Time: 11:33
 */
class WeChat extends BaseOAuth {

    protected $config = 'wechat';
    protected $apiMap = array(
       'login' => array(
           'https://open.weixin.qq.com/connect/qrconnect',
           array(
               '#appid',
               '#redirect_uri',
               'response_type' => 'code',
               '#scope',
               'state'
           )
       ),
        'access' => array(
            'https://api.weixin.qq.com/sns/oauth2/access_token',
            array(
                '#appid',
                '#secret',
                '#code',
                'grant_type' => 'authorization_code'
            )
        ),
        'refresh' => array(
            'https://api.weixin.qq.com/sns/oauth2/refresh_token',
            array(
                '#appid',
                'grant_type' => 'refresh_token',
                '#refresh_token'
            )
        ),
        'info' => array(
            'https://api.weixin.qq.com/sns/userinfo',
            array(
                '#access_token',
                '#openid',
                'lang'
            )
        )
    );

    /**
     * @return array
     */
    public function callback() {
        /**
         * access_token	接口调用凭证
         * expires_in	access_token接口调用凭证超时时间，单位（秒）
         * refresh_token	用户刷新access_token
         * openid	授权用户唯一标识
         * scope	用户授权的作用域，使用逗号（,）分隔
         * unionid	当且仅当该网站应用已获得该用户的userinfo授权时，才会出现该字段。
         */
        $access = $this->getJson('access');
        /**
         * openid	普通用户的标识，对当前开发者帐号唯一
        nickname	普通用户昵称
        sex	普通用户性别，1为男性，2为女性
        province	普通用户个人资料填写的省份
        city	普通用户个人资料填写的城市
        country	国家，如中国为CN
        headimgurl	用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空
        privilege	用户特权信息，json数组，如微信沃卡用户为（chinaunicom）
        unionid	用户统一标识。针对一个微信开放平台帐号下的应用，同一用户的unionid是唯一的。
         */
        $info = $this->getJson('info', $access);
        return array_merge($access, $info);
    }
}