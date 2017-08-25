<?php
namespace Zodream\Domain\Model\Concerns;

use Zodream\Domain\Filter\DataFilter;
use Zodream\Domain\Filter\ModelFilter;

trait ValidateData {
    /**
     * 过滤规则
     * @return array
     */
    protected function rules() {
        return [];
    }

    /**
     * 验证
     * @param array $rules
     * @return bool
     */
    public function validate($rules = array()) {
        if (empty($rules)) {
            $rules = $this->rules();
        }
        $result = true;
        foreach ($rules as $key => $rule) {
            if (is_integer($key) && is_array($rule)) {
                $key = array_shift($rule);
            }
            $result = $result && $this->_validateOne($key, $rule);
        }
        return $result && !$this->hasError();
    }

    private function _validateOne($key, $rule) {
        $method = is_array($rule) ? current($rule) : $rule;
        if (!is_callable($method) &&
            (!is_string($method) || !method_exists($this, $method))) {
            DataFilter::clearError();
            if (DataFilter::validateOne($this, $key, DataFilter::getFilters($rule))) {
                return true;
            }
            $this->setError(DataFilter::getError());
            return false;
        }
        $result = true;
        if (is_string($method)) {
            $method = [$this, $method];
        }
        foreach ((array)$key as $k) {
            if (false !== call_user_func($method, $this->get($k))) {
                continue;
            }
            $result = false;;
            if (is_array($rule) && array_key_exists('message', $rule)) {
                $this->setError($k, str_replace('{key}', $rule['message']));
            }
        }
        return $result;
    }
}