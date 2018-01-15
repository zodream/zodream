<?php
namespace Zodream\Domain\Filter\Filters;

use Zodream\Infrastructure\Interfaces\FilterObject;

class IntFilter extends FilterObject {
    protected $error = '不是整型！';

    protected $_defaultOption = array(
        'min' => PHP_INT_MIN,
        'max' => PHP_INT_MAX
    );

    public function filter($arg) {
        if (!is_numeric($arg)) {
            return null;
        }
        $arg = (int)$arg;
        if ($this->_option['min'] > $arg) {
            return $this->_option['min'];
        }
        if ($this->_option['max'] < $arg) {
            return $this->_option['max'];
        }
        return $arg;
    }

    public function validate($arg) {
        if (is_null($arg) || $arg == '') {
            return true;
        }
        $filtered = $this->filter($arg);
        // 保证bool类型能通过
        return is_bool($arg) || $filtered == $arg;
    }

    public function setOption($option) {
    	if (empty($option)) {
    		$option = array(PHP_INT_MIN);
    	}
    	if (is_string($option)) {
            $option = explode('-', $option);
        }
        if (is_array($option)) {
            $this->setOptionWhenArray($option);
        }
    }

    private function setOptionWhenArray(array $option) {
        if (count($option) < 2) {
            $option[] = PHP_INT_MAX;
        }
        sort($option);
        parent::setOption(array(
            'min' => (int)$option[0],
            'max' => (int)$option[1]
        ));
    }
}