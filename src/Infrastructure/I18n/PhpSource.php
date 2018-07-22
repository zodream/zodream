<?php
namespace Zodream\Infrastructure\I18n;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/25
 * Time: 17:12
 */
class PhpSource extends I18n {

    public function translate($message, $param = [], $name = null) {
        if (empty($message)) {
            return $message;
        }
        parent::translate($message, $param, $name);
        $args = $this->get($this->fileName, array());
        if (!$this->has($name) || !array_key_exists($message, $args)) {
            return $this->format($message, $param);
        }
        return $this->format($args[$message], $param);
    }


    /**
     * 修改源
     */
    public function reset() {
        if ($this->has($this->fileName)) {
            return;
        }
        $file = $this->directory->childFile($this->language.'/'.$this->fileName.'.php');
        if (!$file->exist()) {
            return;
        }
        $args = include (string)$file;
        if (!is_array($args)) {
            return;
        }
        $this->set($this->fileName, $args);
    }
}