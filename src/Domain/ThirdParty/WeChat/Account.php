<?php
namespace Zodream\Domain\ThirdParty\WeChat;
/**
 * 帐号管理
 * User: zx648
 * Date: 2016/8/20
 * Time: 14:38
 */
class Account extends BaseWeChat {
    protected $apiMap = [
        'qrCode' => [
            [
                'https://api.weixin.qq.com/cgi-bin/qrcode/create',
                '#access_token'
            ],
            [
                '#action_info',
                'expire_seconds',
                'action_name'
            ]
        ],
        'shortUrl' => [
            [
                'https://api.weixin.qq.com/cgi-bin/shorturl',
                '#access_token'
            ],
            [
                'action' => 'long2short',
                '#long_url'
            ],
            'POST'
        ],
        'clear' => [
            [
                'https://api.weixin.qq.com/cgi-bin/clear_quota',
                '#access_token'
            ],
            '#appid',
            'POST'
        ]
    ];

    /**
     * @param integer|string $scene
     * @param bool|integer $time IF FALSE, QR_LIMIT_SCENE , OR INT, QR_SCENE
     * @return array [ticket, expire_seconds, url]
     */
    public function getQrCode($scene, $time = false) {
        $data = [
            'action_info' => [
                'scene' => []
            ]
        ];
        if ($time !== false) {
            $data['action_name'] = 'QR_SCENE';
            $data['expire_seconds'] = intval($time);
            $data['action_info']['scene'] = ['scene_id' => intval($scene)];
        } else {
            if (is_integer($scene)) {
                $data['action_name'] = 'QR_LIMIT_SCENE';
                $data['action_info']['scene'] = ['scene_id' => $scene];
            } else {
                $data['action_name'] = 'QR_LIMIT_STR_SCENE';
                $data['action_info']['scene'] = ['scene_str' => $scene];
            }
        }
        return $this->getJson('qrCode', $data);
    }

    public function shortUrl($url) {
        $args = $this->getJson('shortUrl', [
            'action' => 'long2short',
            'long_url' => $url
        ]);
        return array_key_exists('short_url', $args) ? $args['short_url'] : false;
    }
}