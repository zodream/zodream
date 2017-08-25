<?php
namespace Zodream\Domain\Spider;

use Zodream\Infrastructure\Database\Query\Record;
use Zodream\Infrastructure\Disk\File;
use Zodream\Infrastructure\ObjectExpand\JsonExpand;
use Zodream\Infrastructure\Support\Curl;

class Spider {

    protected $data;

    public static function loadFile($file) {
        if (!$file instanceof File) {
            $file = new File($file);
        }
        return new static($file->read());
    }

    public static function loadUrl($url) {
        return new static((new Curl($url))->get());
    }

    public function __construct($data = null) {
        $this->setData($data);
    }

    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    public function getData() {
        return $this->data;
    }

    public function map(callable $callback) {
        $arg = call_user_func($callback, $this->data);
        if (!is_null($arg)) {
            $this->data = $arg;
        }
        return $this;
    }

    public function switch(callable $callback) {
        if (!is_array($this->data)) {
            return $this->map($callback);
        }
        $data = [];
        foreach ($this->data as $key => $item) {
            $arg = $callback($item, $key);
            if (!is_null($arg)) {
                $item = $arg;
            }
            $data[$key] = $item;
        }
        $this->data = $data;
        return $this;
    }

    public function toJson() {
        if (is_string($this->data)) {
            $this->data = JsonExpand::decode($this->data);
        }
        return $this;
    }

    public function toXml() {
        return XmlExpand::decode($this->data);
    }

    public function saveFile($file) {
        if (!$file instanceof File) {
            $file = new File($file);
        }
        $file->write($this->data);
        return $this;
    }

    public function saveTable($table) {
        if (is_array($this->data)) {
            (new Record())->setTable($table)->set($this->data)->insert();
        }
        return $this;
    }
}