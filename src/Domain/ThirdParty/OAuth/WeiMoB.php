<?php
namespace Zodream\Domain\ThirdParty\OAuth;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/9/7
 * Time: 9:19
 */
use Zodream\Infrastructure\Http\Request;

class WeiMoB extends BaseOAuth {

    protected $configKey = 'weimob';

    protected $apiMap = [
        'login' => [
            'https://dopen.weimob.com/fuwu/b/oauth2/authorize',
            [
                'enter' => 'wm',
                'view' => 'pc',
                'response_type' => 'code',
                'state',
                '#redirect_uri',
                'scope' => 'default'
            ]
        ],
        'access' => [
            [
                'https://dopen.weimob.com/fuwu/b/oauth2/token',
                [
                    '#client_id',
                    '#client_secret',
                    'grant_type' => 'authorization_code',
                    '#code',
                    '#redirect_uri',
                    'state'
                ]
            ],
            [],
            'POST'
        ],
        'info' => [
            'http://dopen.weimob.com/api/1_0/open/usercenter/getWeimobUserInfo',
            '#accesstoken'
        ]
    ];

    public function callback() {
        $state = Request::get('state', '');
        if (strpos($state, 'sign:') !== 0) {
            return parent::callback();
        }
        $args = preg_split('/[:;]/', $state);
        if (count($args) != 6) {
            return false;
        }
        $data = [
            $args[0] = $args[1],
            $args[2] = $args[3],
            $args[4] = $args[5],
        ];
        unset($args);
        if ($data['sign'] != md5($this->get('client_secret').$data['endTime'].$data['versionName'])) {
            return false;
        }
        $code = Request::get('code');
        if (empty($code)) {
            return false;
        }
        $data['code'] = $code;
        $this->set($data);
        /**
         * access_token string Access token
        token_type string Bearer Access token的类型目前只支持bearer
        expires_in number 7200（表示7200秒后过期） Access token过期时间
        refresh_token string Refresh token，可用来刷新access_token
        refresh_token_expires_in	number	默认7天	Refresh token有效期
        scope	String	默认default	授权范围
        business_id String	 微盟商户id
        public_account_id String 微盟商户的公众号id
         */
        $access = $this->getJson('access');
        if (!array_key_exists('access_token', $access)) {
            return false;
        }
        $this->set($access);
        return $access;
    }

    /**
     * 获取用户信息
     * @return array|false
     */
    public function getInfo() {
        $args = $this->getJson('info');
        if ($args['code']['errcode'] != 0) {
            return false;
        }
        /**
         * pid string 商户公众号id
        name string 商户公众号名称
        avatarUrl string 商户公众号头像
         */
        return $args['data'];
    }
}