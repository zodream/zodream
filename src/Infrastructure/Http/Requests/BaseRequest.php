<?php
namespace Zodream\Infrastructure\Http\Requests;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/3
 * Time: 9:23
 */
use Zodream\Infrastructure\Base\MagicObject;
abstract class BaseRequest extends MagicObject {
    /**
     * 格式化
     * @param array|string $data
     * @return array|string
     */
    protected function _clean($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                unset($data[$key]);
                $data[strtolower($this->_clean($key))] = $this->_clean($value);
            }
        } else if (defined('APP_SAFE') && APP_SAFE){
            $data = htmlspecialchars($data, ENT_COMPAT);
        }
        return $data;
    }

    protected function setValues(array $data) {
        $this->set($this->_clean($data));
    }

    public function get($name = null, $default = null) {
        return parent::get(strtolower($name), $default);
    }
}