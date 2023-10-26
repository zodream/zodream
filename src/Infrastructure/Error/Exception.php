<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Error;

class Exception extends \Exception {

    public function __construct($message = '', $code = 0, \Exception $previous = null) {
        if (is_string($message)) {
            $message = trans($message);
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