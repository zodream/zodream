<?php
namespace Zodream\Domain\ThirdParty\WeChat\Platform;


use Zodream\Domain\ThirdParty\WeChat\Aes;
use Zodream\Infrastructure\Base\MagicObject;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\ObjectExpand\XmlExpand;
use Zodream\Infrastructure\Traits\EventTrait;
use Zodream\Service\Config;
use Zodream\Service\Factory;

/**
 * 推送给平台授权相关通知
 * @package Zodream\Domain\ThirdParty\WeChat\Platform
 * @property string $componentVerifyTicket
 * @property string $createTime
 * @property string $infoType  unauthorized是取消授权，updateauthorized是更新授权，authorized是授权成功通知
 * @property string $authorizerAppid 公众号
 * @property string $authorizationCode   授权码，可用于换取公众号的接口调用凭据
 * @property string $authorizationCodeExpiredTime  授权码过期时间
 */
class Notify extends MagicObject {
    protected $configKey = 'wechat.platform';

    use EventTrait;
    const TYPE_ComponentVerifyTicket = 'component_verify_ticket';
    const TYPE_Unauthorized = 'unauthorized';
    const TYPE_UpdateAuthorized = 'updateauthorized';
    const TYPE_Authorized = 'authorized';

    protected $xml;

    protected $aesKey;

    protected $encryptType;

    protected $appId;

    public function __construct(array $config = array()) {
        $config = array_merge(Config::getValue($this->configKey, array(
            'aes_key' => '',
            'component_appid' => ''
        )), $config);
        $this->aesKey = $config['aes_key'];
        $this->appId = $config['component_appid'];
        $this->get();
        $this->setComponentVerifyTicket();
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

    public function getXml() {
        return $this->xml;
    }

    public function getEvent() {
        return $this->infoType;
    }

    protected function getData() {
        Factory::log()->info('WECHAT NOTIFY: '.$this->xml);
        $data = (array)XmlExpand::decode($this->xml, false);
        $encryptStr = $data['Encrypt'];
        $aes = new Aes($this->aesKey, $this->appId);
        $this->xml = $aes->decrypt($encryptStr);
        return (array)XmlExpand::decode($this->xml, false);
    }

    public function setComponentVerifyTicket() {
        if ($this->infoType != self::TYPE_ComponentVerifyTicket) {
            return $this;
        }
        Factory::cache()->set('WeChatThirdComponentVerifyTicket',
            $this->componentVerifyTicket);
        return $this;
    }

    public function run() {
        $this->invoke($this->getEvent(), [$this]);
        return 'success';
    }
}