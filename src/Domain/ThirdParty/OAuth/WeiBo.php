<?php
namespace Zodream\Domain\ThirdParty\OAuth;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/13
 * Time: 14:08
 */
class WeiBo extends BaseOAuth {

    /**
     * EXPAMLE:
     * 'weibo' => [
        'client_id' => '',
        'redirect_uri' => '',
        'client_secret' => ''
    ]
     * @var string
     */
    protected $configKey = 'weibo';

    protected $apiMap = array(
       'login' => array(
           'https://api.weibo.com/oauth2/authorize',
           array(
               '#client_id',
               '#redirect_uri',
               'scope',
               'state',
               'display',
               'forcelogin',
               'language'
           )
       ),
        'access' => array(
            'https://api.weibo.com/oauth2/access_token',
            array(
                '#client_id',
                '#client_secret',
                'grant_type' => 'authorization_code',
                '#code',
                '#redirect_uri'
            ),
            'post'
        ),
        'token' => array(
            'https://api.weibo.com/oauth2/get_token_info',
            '#access_token',
            'post'
        ),
        'delete' => array(
            'https://api.weibo.com/oauth2/revokeoauth2',
            '#access_token'
        ),
        'info' => [
            'https://api.weibo.com/2/users/show.json',
            [
                '#access_token',
                '#uid',              //参数uid与screen_name二者必选其一
                'screen_name'
            ]
        ]
    );

    public function callback() {
        if (parent::callback() === false) {
            return false;
        }
        /**
         * access_token
         * expires_in
         * remind_in
         * uid
         */
        $access = $this->getJson('access');
        if (!is_array($access) || !array_key_exists('access_token', $access)) {
            return false;
        }
        /**
         * uid	string	授权用户的uid。
         * appkey	string	access_token所属的应用appkey。
         * scope	string	用户授权的scope权限。
         * create_at	string	access_token的创建时间，从1970年到创建时间的秒数。
         * expire_in	string	access_token的剩余时间，单位是秒数。
         */
        //$info = $this->getJson('token', $access);
        //$access['uid'] = $info['uid'];
        $access['identity'] = $access['access_token'];
        $this->set($access);
        return $access;
    }

    public function getInfo() {
        /*
         * 
         * id	int64	用户UID
idstr	string	字符串型的用户UID
screen_name	string	用户昵称
name	string	友好显示名称
province	int	用户所在省级ID
city	int	用户所在城市ID
location	string	用户所在地
description	string	用户个人描述
url	string	用户博客地址
profile_image_url	string	用户头像地址（中图），50×50像素
profile_url	string	用户的微博统一URL地址
domain	string	用户的个性化域名
weihao	string	用户的微号
gender	string	性别，m：男、f：女、n：未知
followers_count	int	粉丝数
friends_count	int	关注数
statuses_count	int	微博数
favourites_count	int	收藏数
created_at	string	用户创建（注册）时间
following	boolean	暂未支持
allow_all_act_msg	boolean	是否允许所有人给我发私信，true：是，false：否
geo_enabled	boolean	是否允许标识用户的地理位置，true：是，false：否
verified	boolean	是否是微博认证用户，即加V用户，true：是，false：否
verified_type	int	暂未支持
remark	string	用户备注信息，只有在查询用户关系时才返回此字段
status	object	用户的最近一条微博信息字段 详细
allow_all_comment	boolean	是否允许所有人对我的微博进行评论，true：是，false：否
avatar_large	string	用户头像地址（大图），180×180像素
avatar_hd	string	用户头像地址（高清），高清头像原图
verified_reason	string	认证原因
follow_me	boolean	该用户是否关注当前登录用户，true：是，false：否
online_status	int	用户的在线状态，0：不在线、1：在线
bi_followers_count	int	用户的互粉数
lang	string	用户当前的语言版本，zh-cn：简体中文，zh-tw：繁体中文，en：英语
         */
        $user = $this->getJson('info');
        if (!is_array($user) || !array_key_exists('screen_name', $user)) {
            return false;
        }
        $user['username'] = $user['screen_name'];
        $user['avatar'] = $user['profile_image_url'];
        $user['sex'] = $user['gender'] == 'm' ? '男' : '女';
        $this->set($user);
        return $user;
    }
}