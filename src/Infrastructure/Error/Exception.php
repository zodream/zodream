<?php
namespace Zodream\Infrastructure\Error;

use Zodream\Service\Factory;

class Exception extends \Exception {

    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        if (is_string($message)) {
            $message = Factory::i18n()->translate($message);
        }
        parent::__construct($message, $code, $previous);
    }

    public function getName() {
        return 'Exception';
    }

    /**
     * 设置路径
     * @param string $file
     * @return $this
     */
    public function setFile($file) {
        if (!is_null($file)) {
            $this->file = $file;
        }
        return $this;
    }

    /**
     * 设置行号
     * @param string|integer $line
     * @return $this
     */
    public function setLine($line) {
        if (!is_null($line)) {
            $this->line = $line;
        }
        return $this;
    }
}