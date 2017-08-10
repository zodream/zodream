<?php
namespace Zodream\Domain\Model\Concerns;

use Zodream\Infrastructure\Http\Request;
use Zodream\Helpers\Str;

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
        if (!is_array($key)) {
            $key = [$key => $value];
        }
        foreach ($key as $k => $item) {
            $method = sprintf('set%sAttribute', Str::studly($k));
            if (method_exists($this, $method)) {
                $this->{$method}($item);
                continue;
            }
            if (property_exists($this, $k)) {
                $this->$k = $item;
                continue;
            }
            $this->_data[$k] = $item;
        }
        return $this;
    }

    /**
     * 设置旧值
     * @param null $data
     * @return $this
     */
    public function setOldData($data = null) {
        if (is_null($data)) {
            $data = $this->_data;
        }
        $this->isNewRecord = false;
        $this->_oldData = array_merge($this->_oldData, $data);
        return $this;
    }

    /**
     * 初始化旧值
     * @return $this
     */
    public function initOldData() {
        $this->isNewRecord = true;
        $this->_oldData = [];
        return $this;
    }
}