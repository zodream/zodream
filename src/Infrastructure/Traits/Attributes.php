<?php
namespace Zodream\Infrastructure\Traits;

use Zodream\Helpers\Arr;

trait Attributes {


    /**
     * `has` determines if an attribute "has" been defined
     *
     * @param   string  $attribute  attribute name
     *
     * @api     public
     * @return  boolean
     */
    public function has($attribute) {
        return $this->hasAttribute($attribute);
    }

    /**
     * `__isset` determines if an attribute:
     * (1) has been defined
     * (2) has been set
     * (3) is not NULL
     *
     * @param   string  $attribute
     *
     * @return  boolean
     */
    public function __isset($attribute) {
        return $this->hasAttribute($attribute);
    }
    /**
     * `get` returns attributes value or, if attribute value is null, returns default value if given
     *
     * @param   string  $name     attribute name
     * @param   mixed   $default  [optional] default return value
     *
     * @return  mixed
     */
    public function get($name, $default = null) {
        return $this->getAttribute($name, $default);
    }

    /**
     * 获取值
     * @param string $key 关键字
     * @param string $default 默认返回值
     * @return array|string
     */
    public function getAttribute($key = null, $default = null) {
        if (empty($key)) {
            return $this->__attributes;
        }
        if (!is_array($this->__attributes)) {
            $this->__attributes = (array)$this->__attributes;
        }
        if ($this->has($key)) {
            return $this->__attributes[$key];
        }
        if (strpos($key, ',') !== false) {
            $result = Arr::getValues($key, $this->__attributes, $default);
        } else {
            $result = Arr::getChild($key, $this->__attributes, is_object($default) ? null : $default);
        }
        if (is_callable($default)) {
            return $default($result);
        }
        return $result;
    }

    /**
     * 设置值
     * @param string|array $key
     * @param string $value
     * @return $this
     */
    public function setAttribute($key, $value = null) {
        if (is_object($key)) {
            $key = (array)$key;
        }
        if (is_array($key)) {
            $this->__attributes = array_merge($this->__attributes, $key);
            return $this;
        }
        if (empty($key)) {
            return $this;
        }
        $this->__attributes[$key] = $value;
        return $this;
    }

    /**
     * 判断是否有
     * @param string|null $key 如果为null 则判断是否有数据
     * @return bool
     */
    public function hasAttribute($key) {
        if (is_null($key)) {
            return !empty($this->__attributes);
        }
        if (empty($this->__attributes)) {
            return false;
        }
        return array_key_exists($key, $this->__attributes);
    }

    public function deleteAttribute($key) {
        foreach (func_get_args() as $value) {
            unset($this->__attributes[$value]);
        }
        return $this;
    }

    public function clearAttribute() {
        $this->__attributes = array();
        return $this;
    }

    /**
     * `__get` is an alias of `get`
     *
     * @param   string  $attribute     attribute name
     *
     * @return  mixed
     */
    public function __get($attribute) {
        return $this->getAttribute($attribute);
    }
    /**
     * `set` attributes value
     *
     * @param   string  $attribute  attribute name
     * @param   mixed   $value      attribute value
     *
     * @return  $this
     */
    public function set($attribute, $value = null) {
        return $this->setAttribute($attribute, $value);
    }

    /**
     * `__set` is an alias of `set`
     *
     * @param   string  $attribute  attribute name
     * @param   mixed   $value      attribute value
     *
     * @return  $this->set()
     */
    public function __set($attribute, $value) {
        return $this->setAttribute($attribute, $value);
    }
    /**
     * `__unset` clears an attribute's value
     *
     * @param   string  $attribute  attribute name
     *
     * @return  $this
     */
    public function __unset($attribute) {
        return $this->deleteAttribute($attribute);
    }

}