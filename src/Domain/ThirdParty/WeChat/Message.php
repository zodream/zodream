<?php
namespace Zodream\Domain\ThirdParty\WeChat;

use Zodream\Infrastructure\Base\MagicObject;
use Zodream\Infrastructure\ObjectExpand\StringExpand;
use Zodream\Infrastructure\ObjectExpand\XmlExpand;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Traits\EventTrait;
use Zodream\Service\Config;
use Zodream\Service\Factory;

/**
 * 消息管理
 * @package Zodream\Domain\ThirdParty\WeChat
 *
 * @property string $toUserName
 * @property string $fromUserName
 * @property integer $createTime
 * @property string $msgType
 * //消息
 * @property integer $msgId
 * //@property string //$msgType
 * //文本
 * @property string $content
 * //图片消息
 * @property string $picUrl
 * @property string $mediaId
 * //语音消息
 * @property string $format
 * @property string $recognition  开通语音识别
 * //视频消息 小视频消息
 * @property string $thumbMediaId
 * //地理位置消息
 * @property float $location_X
 * @property float $location_Y
 * @property integer $scale
 * @property string $label
 * //链接消息
 * @property string $title
 * @property string $description
 * @property string $url
 *
 * @property string $event
 * //自定义菜单
 * @property string $eventKey
 */
class Message extends MagicObject {

    use EventTrait;

    protected $configKey = 'wechat';

    protected $xml;

    protected $token;

    protected $aesKey;

    protected $encryptType;

    protected $appId;

    public function __construct(array $config = array()) {
        $config = array_merge(Config::getValue($this->configKey, array(
            'aes_key' => '',
            'appid' => ''
        )), $config);
        $this->token = $config['token'];
        $this->aesKey = $config['aes_key'];
        $this->appId = $config['appid'];
        $this->encryptType = Request::get('encrypt_type');
        $this->get();
    }

    public function get($key = null, $default = null) {
        if (empty($this->_data)) {
            $this->setData();
        }
        return parent::get(lcfirst($key), $default);
    }

    public function setData() {
        if (empty($this->xml)) {
            $this->xml = Request::input();
        }
        if (!empty($this->xml)) {
            $args = $this->getData();
            foreach ($args as $key => $item) {
                $this->set(lcfirst($key), $item);
            }
        }
        return $this;
    }

   /**
     * 分离扫码关注事件
     * @return string
     */
    public function getEvent() {
        if (!$this->isEvent()) {
            return EventEnum::Message;
        }
        // ADD SCAN SUBSCRIBE EVENT
        if ($this->event == EventEnum::Subscribe
            && strpos($this->eventKey, 'qrscene_') === 0) {
            $this->eventKey = Str::firstReplace($this->eventKey, 'qrscene_');
            return EventEnum::ScanSubscribe;
        }
        return $this->event;
    }

    /**
     * 判断是否是事件推送
     * @return bool
     */
    public function isEvent() {
        return $this->msgType == 'event';
    }

    /**
     * 判断是否是消息推送
     * @return bool
     */
    public function isMessage() {
        return !$this->isEvent();
    }

    public function getXml() {
        return $this->xml;
    }

    /**
     * 来源者
     * @return string
     */
    public function getFrom() {
        return $this->get('FromUserName');
    }

    /**
     * 接收方
     * @return string
     */
    public function getTo() {
        return $this->get('ToUserName');
    }

    protected function getData() {
        Factory::log()->info('WECHAT MESSAGE: '.$this->xml);
        $data = (array)Xml::decode($this->xml, false);
        if ($this->encryptType != 'aes') {
            return $data;
        }
        $encryptStr = $data['Encrypt'];
        $aes = new Aes($this->aesKey, $this->appId);
        $this->xml = $aes->decrypt($encryptStr);
        $this->appId = $aes->getAppId();
        return (array)Xml::decode($this->xml, false);
    }

    /**
     * 当前操作是否是验证
     * @return bool
     */
    public function isValid() {
        return Request::get(true)->has('signature')
            || Request::get(true)->has('msg_signature');
    }

    /**
     * 验证
     * @param string $str
     * @return bool
     */
    protected function checkSignature($str = '') {
        $signature = Request::get('signature');
        $signature = Request::get('msg_signature', $signature); //如果存在加密验证则用加密验证段
        $timestamp = Request::get('timestamp');
        $nonce = Request::get('nonce');

        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce, $str);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        return $tmpStr == $signature;
    }

    /**
     * 验证
     */
    public function valid() {
        $echoStr = Request::get('echostr');
        if (!is_null($echoStr)) {
            if ($this->checkSignature()) {
                exit($echoStr);
            }
            throw new \Exception('no access');
        }
        $encryptStr = '';
        if (Request::isPost()) {
            $data = (array)Xml::decode($this->xml, false);
            if ($this->encryptType != 'aes') {
                return $data;
            }
            $encryptStr = $data['Encrypt'];
        }
        if (!$this->checkSignature($encryptStr)) {
            throw new \Exception('no access');
        }
        return true;
    }

    /**
     * 无法回复时自动返回success
     * @return MessageResponse
     */
    public function run() {
        $response = new MessageResponse($this->token,
            $this->aesKey,
            $this->encryptType,
            $this->appId);
        $response->setFromUseName($this->getTo())
            ->setToUseName($this->getFrom());
        $this->invoke($this->getEvent(), [$this, $response]);
        return $response;
    }
}