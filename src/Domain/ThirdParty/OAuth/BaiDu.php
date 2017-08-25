<?php
namespace Zodream\Domain\ThirdParty\OAuth;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/10
 * Time: 17:44
 */
class BaiDu extends BaseOAuth {
    protected $configKey= 'baidu';

    protected $apiMap = array(
        'login' => array(
            'http://openapi.baidu.com/oauth/2.0/authorize',
            array(
                '#client_id',
                'response_type' => 'code',
                '#redirect_uri',
                'scope',
                'state',
                'display',
                'force_login',
                'login_type'
            )
        ),
        'access' => array(
            'https://openapi.baidu.com/oauth/2.0/token',
            array(
                'grant_type' => 'authorization_code',
                '#code',
                '#client_id',
                '#client_secret',
                '#redirect_uri'
            )
        ),
        'refresh' => array(
            'https://openapi.baidu.com/oauth/2.0/token',
            array(
                'grant_type' => 'refresh_token',
                '#refresh_token',
                '#client_id',
                '#client_secret',
                'scope'
            )
        ),
        'uid' => array(
            'https://openapi.baidu.com/rest/2.0/passport/users/getLoggedInUser',
            array(
                '#access_token',
                'callback'
            ),
            'post'
        ),
        'info' => array(
            'https://openapi.baidu.com/rest/2.0/passport/users/getInfo',
            array(
                '#access_token',
                'callback'
            )
        )
    );

    /**
     * @return array|false
     */
    public function callback() {
        if (parent::callback() === false) {
            return false;
        }
        /**
         * access_token：要获取的Access Token；
         * expires_in：Access Token的有效期，以秒为单位；请参考“Access Token生命周期”
         * refresh_token：用于刷新Access Token 的 Refresh Token,所有应用都会返回该参数；（10年的有效期）
         * scope：Access Token最终的访问范围，即用户实际授予的权限列表（用户在授权页面时，有可能会取消掉某些请求的权限），关于权限的具体信息参考“权限列表”一节；
         * session_key：基于http调用Open API时所需要的Session Key，其有效期与Access Token一致；
         * session_secret：基于http调用Open API时计算参数签名用的签名密钥。
         */
        $access = $this->getJson('access');
        if (!is_array($access) || !array_key_exists('access_token', $access)) {
            return false;
        }
        $access['identity'] = $access['access_token'];
        $this->set($access);
        return $access;
    }

    public function getInfo() {
        /**
         * uid 当前登录用户的数字ID。
         * uname
         * portrait 当前登录用户的头像。
         *      small image: http://tb.himg.baidu.com/sys/portraitn/item/{$portrait}
         *      large image: http://tb.himg.baidu.com/sys/portrait/item/{$portrait}
         */

        /**
         * userid	uint	是	67411167	当前登录用户的数字ID
        username	string	否	robin928	当前登录用户的用户名，值可能为空
        realname	string	否	阳光	用户真实姓名，可能为空
        portrait	string	否	e2c1776c31393837313031319605	当前登录用户的头像
        small image: http://tb.himg.baidu.com/sys/portraitn/item/{$portrait} large image: http://tb.himg.baidu.com/sys/portrait/item/{$portrait}

        userdetail	string	否	喜欢自由	自我简介，可能为空
        birthday	string	否	1987-01-01	生日，以yyyy-mm-dd格式显示
        marriage	string	否	已婚	婚姻状况
        sex	string	否	男	性别
        blood	string	否	O	血型
        figure	string	否	小巧	体型
        constellation	string	否	射手	星座
        education	string	否	大学/专科	学历
        trade	string	否	计算机/电子产品	当前职业
        job	string	否	未知	职位
         */
        $user = $this->getJson('info');
        if (!is_array($user) || !array_key_exists('userid', $user)) {
            return false;
        }
        $user['avatar'] = $user['portrait'];
        $this->set($user);
        return $user;
    }
}