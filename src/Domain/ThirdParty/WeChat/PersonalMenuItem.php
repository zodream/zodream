<?php
namespace Zodream\Domain\ThirdParty\WeChat;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/20
 * Time: 0:01
 */
class PersonalMenuItem extends MenuItem {

    const IOS = 1;
    const Android = 2;
    const Others = 3;

    const MALE = 1;
    const FEMALE = 2;

    protected $groupId;
    protected $sex;
    protected $os;
    protected $country;
    protected $province;
    protected $city;
    protected $language;

    public function setGroupId($arg) {
        $this->groupId = $arg;
        return $this;
    }

    public function setSex($arg) {
        $this->sex = $arg;
        return $this;
    }

    public function setOs($arg) {
        $this->os = $arg;
        return $this;
    }

    public function setCountry($arg) {
        $this->country = $arg;
        return $this;
    }

    public function setProvince($arg) {
        $this->province = $arg;
        return $this;
    }

    public function setCity($arg) {
        $this->city = $arg;
        return $this;
    }

    public function setLanguage($arg) {
        $this->language = $arg;
        return $this;
    }

    public function toArray() {
        $data = parent::toArray();
        if (!array_key_exists('button', $data)) {
            return $data;
        }
        $data['matchrule'] = [];
        if (!empty($this->groupId)) {
            $data['matchrule']['group_id'] = $this->groupId;
        }
        if (!empty($this->sex)) {
            $data['matchrule']['sex'] = $this->sex;
        }
        if (!empty($this->os)) {
            $data['matchrule']['client_platform_type'] = $this->os;
        }
        if (!empty($this->country)) {
            $data['matchrule']['country'] = $this->country;
        }
        if (!empty($this->province)) {
            $data['matchrule']['province'] = $this->province;
        }
        if (!empty($this->city)) {
            $data['matchrule']['city'] = $this->city;
        }
        if (!empty($this->language)) {
            $data['matchrule']['language'] = $this->language;
        }
        if (empty($data['matchrule'])) {
            unset($data['matchrule']);
        }
        return $data;
    }
}