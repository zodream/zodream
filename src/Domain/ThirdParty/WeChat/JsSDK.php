<?php
namespace Zodream\Domain\ThirdParty\WeChat;
use Zodream\Infrastructure\ObjectExpand\StringExpand;
use Zodream\Service\Factory;
use Zodream\Service\Routing\Url;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2017/1/3
 * Time: 13:46
 */
class JsSDK extends BaseWeChat {
    protected $apiMap = [
        'ticket' => [
            'https://api.weixin.qq.com/cgi-bin/ticket/getticket',
            [
                '#access_token',
                'type' => 'jsapi'
            ]
        ]
    ];


    public function getTicket() {
        $key = 'jsApi_ticket'.$this->get('appid');
        if (Factory::cache()->has($key)) {
            return Factory::cache()->get($key);
        }
        $args = $this->getJson('ticket');
        if (!is_array($args)) {
            throw new \Exception('HTTP ERROR!');
        }
        if (!array_key_exists('ticket', $args)) {
            throw new \Exception(isset($args['errmsg']) ? $args['errmsg'] : 'GET JS API TICKET ERROR!');
        }
        Factory::cache()->set($key, $args['ticket'], $args['expires_in']);
        return $args['ticket'];
    }

    protected function getJsSign(array $data) {
        ksort($data);
        reset($data);
        $arg = [];
        foreach ($data as $key => $item) {
            if ($this->checkEmpty($item) || $key == 'sign') {
                continue;
            }
            $arg[] = $key.'='.$item;
        }
        return sha1(implode('&', $arg));
    }

    public function apiConfig($apiList = array()) {
        Factory::view()->registerJsFile('http://res.wx.qq.com/open/js/jweixin-1.0.0.js');
        $appId = $this->get('appid');
        $data = [
            'noncestr' => StringExpand::random(),
            'jsapi_ticket' => $this->getTicket(),
            'timestamp' => time(),
            'url' => (string)Url::to(null, null, true)
        ];
        $sign = $this->getJsSign($data);
        $apiList = implode("','", $apiList);
        $debug = defined('DEBUG') && DEBUG ? 'true' : 'false';
        return <<<JS
wx.config({
    debug: {$debug}, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
    appId: '{$appId}', // 必填，公众号的唯一标识
    timestamp: {$data['timestamp']}, // 必填，生成签名的时间戳
    nonceStr: '{$data['noncestr']}', // 必填，生成签名的随机串
    signature: '{$sign}',// 必填，签名，见附录1
    jsApiList: ['{$apiList}'] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
});
JS;
    }
}