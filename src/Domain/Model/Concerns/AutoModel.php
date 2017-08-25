<?php
namespace Zodream\Domain\Model\Concerns;

use Zodream\Infrastructure\Http\Request;

/**
 * Created by PhpStorm.
 * User: ZoDream
 * Date: 2017/5/7
 * Time: 14:12
 */
trait AutoModel {

    protected $_oldData = [];

    /**
     * 转载数据
     * @param null $data
     * @param null $key
     * @return bool
     */
    public function load($data = null, $key = null) {
        if (is_string($data)) {
            $key = $data;
            $data = null;
        }
        if (Request::isPost()) {
            $data = Request::post($key);
        }
        if (empty($data)) {
            return false;
        }
        $this->set($data);
        return true;
    }

    public function set($key, $value = null){
        if (empty($key)) {
            return $this;
        }
        $this->setOldData();
        if (!is_array($key)) {
            $key = [$key => $value];
        }
        foreach ($key as $k => $item) {
            if (property_exists($this, $k)) {
                $this->$k = $item;
                continue;
            }
            $this->_data[$k] = $item;
        }
        return $this;
    }

    protected function setOldData() {
        if ($this->isNewRecord || !empty($this->_oldData)) {
            return;
        }
        $this->_oldData = $this->_data;
    }
}