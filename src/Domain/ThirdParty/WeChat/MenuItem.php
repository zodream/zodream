<?php
namespace Zodream\Domain\ThirdParty\WeChat;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/19
 * Time: 22:37
 */
use Zodream\Infrastructure\Base\ZObject;

class MenuItem extends ZObject {
    const CLICK = 'click';
    const VIEW = 'view';
    const SCAN_CODE_MSG = 'scancode_waitmsg';
    const SCAN_CODE_PUSH = 'scancode_push';
    const SYSTEM_PHOTO = 'pic_sysphoto';
    const SYSTEM_PHOTO_ALBUM = 'pic_photo_or_album';
    const WEI_XIN_PHOTO = 'pic_weixin';
    const LOCATION = 'location_select';
    const MEDIA = 'media_id';
    const VIEW_LIMITED = 'view_limited';

    protected $type;
    protected $name;
    protected $key;
    protected $url;
    protected $mediaId;
    /**
     * @var MenuItem[]
     */
    protected $menu = [];

    public function __construct() {
    }

    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    public function setName($arg) {
        $this->name = $arg;
        return $this;
    }

    public function setKey($arg) {
        $this->setType(self::CLICK);
        $this->key = $arg;
        return $this;
    }

    public function setUrl($arg) {
        $this->setType(self::VIEW);
        $this->url = $arg;
        return $this;
    }

    public function setMediaId($arg) {
        if (empty($this->type)) {
            $this->setType(self::MEDIA);
        }
        $this->mediaId = $arg;
        return $this;
    }

    /**
     *
     * @param MenuItem[]|MenuItem $arg
     * @return $this
     */
    public function setMenu($arg) {
        if (is_array($arg)) {
            $this->menu = $arg;
        } else {
            $this->menu[] = $arg;
        }
        return $this;
    }

    /**
     *
     */
    public function toArray() {
        if (!empty($this->type)) {
            return $this->getHasType();
        }
        if (!empty($this->name)) {
            $args = array_splice($this->menu, 0, 5);
            return [
                'name' => $this->name,
                'sub_button' => array_map([$this, 'getArray'], $args)
            ];
        }
        $args = array_splice($this->menu, 0, 3);
        return [
            'button' => array_map([$this, 'getArray'], $args)
        ];
    }

    protected function getArray(MenuItem $item) {
        return $item->toArray();
    }

    protected function getHasType() {
        $data = [
            'type' => $this->type,
            'name' => $this->name
        ];
        if (in_array($this->type, [self::CLICK,
            self::SCAN_CODE_MSG,
            self::SCAN_CODE_PUSH,
            self::SYSTEM_PHOTO,
            self::SYSTEM_PHOTO_ALBUM,
            self::WEI_XIN_PHOTO,
            self::LOCATION
        ])) {
            $data['key'] = $this->key;
            return $data;
        }
        if (in_array($this->type, [self::VIEW])) {
            $data['url'] = $this->url;
            return $data;
        }
        if (in_array($this->type, [self::MEDIA, self::VIEW_LIMITED])) {
            $data['media_id'] = $this->mediaId;
            return $data;
        }
        return $data;
    }

}