<?php
namespace Zodream\Domain\ThirdParty\WeChat;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/22
 * Time: 19:33
 */
use Zodream\Infrastructure\ObjectExpand\StringExpand;
use Zodream\Infrastructure\ObjectExpand\XmlExpand;
use Zodream\Service\Factory;

class MessageResponse {

    protected $data = [];

    protected $aesKey;

    protected $encryptType;

    protected $appId;

    protected $token;

    public function __construct($token = null,
                                $aesKey = null,
                                $encryptType = null,
                                $appId = null) {
        $this->setCreateTime(time());
        $this->aesKey = $aesKey;
        $this->appId = $appId;
        $this->encryptType = $encryptType;
        $this->token = $token;
    }

    public function setData($name, $value, $isCData = true) {
        if ($isCData && !is_array($value)) {
            $value = [
                '@cdata' => $value
            ];
        }
        $this->data[$name] = $value;
        return $this;
    }

    public function addData($name, $value) {
        if (!array_key_exists($name, $this->data)) {
            $this->data[$name] = ['item' => []];
        }
        $this->data[$name]['item'][] = $value;
        return $this;
    }


    public function setType($arg) {
        return $this->setData('MsgType', $arg);
    }

    public function setText($arg) {
        return $this->setType(MessageEnum::Text)->setData('Content', $arg);
    }

    public function setImage($mediaId) {
        return $this->setType(MessageEnum::Image)->setData('Image', [
            'MediaId' => [
                '@cdata' => $mediaId
            ]
        ]);
    }

    public function setVoice($mediaId) {
        return $this->setType(MessageEnum::Voice)->setData('Voice', [
            'MediaId' => [
                '@cdata' => $mediaId
            ]
        ]);
    }

    public function setVideo($mediaId, $title = null, $description = null) {
        $data = [
            'MediaId' => [
                '@cdata' => $mediaId
            ]
        ];
        if (!empty($title)) {
            $data['Title'] = [
                '@cdata' => $title
            ];
        }
        if (!empty($description)) {
            $data['Description'] = [
                '@cdata' => $description
            ];
        }
        return $this->setType(MessageEnum::Video)->setData('Video', $data);
    }

    public function setMusic($mediaId,
                             $title = null,
                             $description = null,
                             $musicUrl = null,
                             $hQMusicUrl = null,
                             $thumbMediaId = null) {
        $data = [
            'MediaId' => [
                '@cdata' => $mediaId
            ]
        ];
        if (!empty($title)) {
            $data['Title'] = [
                '@cdata' => $title
            ];
        }
        if (!empty($description)) {
            $data['Description'] = [
                '@cdata' => $description
            ];
        }
        if (!empty($musicUrl)) {
            $data['MusicUrl'] = [
                '@cdata' => $musicUrl
            ];
        }
        if (!empty($hQMusicUrl)) {
            $data['HQMusicUrl'] = [
                '@cdata' => $hQMusicUrl
            ];
        }
        if (!empty($thumbMediaId)) {
            $data['ThumbMediaId'] = [
                '@cdata' => $thumbMediaId
            ];
        }
        return $this->setType(MessageEnum::Music)->setData('Music', $data);
    }

    public function setNews(array $arg) {
        if (!array_key_exists('item', $arg)) {
            $arg = [
                'item' => [
                    $arg
                ],
            ];
        }
        foreach ($arg['item'] as &$item) {
            foreach ($item as &$value) {
                if (is_array($value)) {
                    continue;
                }
                $value = [
                    '@cdata' => $value
                ];
            }
        }
        return $this->setType(MessageEnum::News)
            ->setData('ArticleCount', count($arg['item']), false)
            ->setData('Articles', $arg);
    }

    public function addNews($title = null,
                            $description = null,
                            $picUrl = null,
                            $url = null) {
        if (!array_key_exists('ArticleCount', $this->data)) {
            $this->setType(MessageEnum::News)
                ->setData('ArticleCount', 0, false);
        }
        $data = [];
        if (!empty($title)) {
            $data['Title'] = [
                '@cdata' => $title
            ];
        }
        if (!empty($description)) {
            $data['Description'] = [
                '@cdata' => $description
            ];
        }
        if (!empty($picUrl)) {
            $data['PicUrl'] = [
                '@cdata' => $picUrl
            ];
        }
        if (!empty($url)) {
            $data['Url'] = [
                '@cdata' => $url
            ];
        }
        $this->data['ArticleCount'] ++;
        return $this->addData('Articles', $data);
    }

    /**
     * 转发客服， 为空自动转发
     * @param string $account
     * @return MessageResponse
     */
    public function setService($account = null) {
        if (!empty($account)) {
            $this->setData('TransInfo', [
                'KfAccount' => [
                    '@cdata' => $account
                ]
            ]);
        }
        return $this->setType(MessageEnum::Service);
    }

    public function setToUseName($arg) {
        return $this->setData('ToUserName', $arg);
    }

    public function setFromUseName($arg) {
        return $this->setData('FromUserName', $arg);
    }

    public function setCreateTime($arg) {
        return $this->setData('CreateTime', $arg, false);
    }

    /**
     * 判断是否需要发送内容
     * @return bool
     */
    public function isEmpty() {
        return !isset($this->data['MsgType']);
    }

    /**
     * 自动回复内容
     * @return bool
     */
    public function sendContent() {
        if ($this->isEmpty()) {
            return Factory::response()->sendHtml('success')->send();
        }
        $xml = $this->makeXml();
		Factory::log()->info('MESSAGE RESPONSE:'.$xml);
        return Factory::response()->sendXml($xml)->send();
    }

    protected function makeXml() {
        $xml = XmlExpand::encode($this->data, 'xml');
        if ($this->encryptType != 'aes') {
            return $xml;
        }
        $aes = new Aes($this->aesKey, $this->appId);
        $encrypt = $aes->encrypt($xml);
        $timestamp = time();
        $nonce = StringExpand::random();
        $tmpArr = array($this->token, $timestamp, $nonce, $encrypt);//比普通公众平台多了一个加密的密文
        sort($tmpArr, SORT_STRING);
        $signature = implode($tmpArr);
        $signature = sha1($signature);
        return XmlExpand::encode([
            'Encrypt' => [
                '@cdata' => $encrypt
            ],
            'MsgSignature' => [
                '@cdata' => $signature
            ],
            'TimeStamp' => $timestamp,
            'Nonce' => [
                '@cdata' => $nonce
            ]
        ], 'xml');
    }
}