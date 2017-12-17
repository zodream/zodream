<?php
namespace Zodream\Domain\Spider;

use Zodream\Helpers\Html as HtmlExpand;
use Zodream\Database\Query\Record;
use Zodream\Disk\File;
use Zodream\Helpers\JsonExpand;
use Zodream\Http\Http;

class Spider {

    protected $data;

    public static function loadFile($file) {
        if (!$file instanceof File) {
            $file = new File($file);
        }
        return new static($file->read());
    }

    public static function loadUrl($url) {
        return new static((new Http($url))->get());
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

    public function each(callable $callback) {
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

    public function match($pattern, $callback = null) {
        if (!empty($callback) && is_callable($callback)) {
            $this->data = preg_replace_callback($pattern, $this->data, $callback);
            return $this;
        }
        if (preg_match($pattern, $this->data, $match)) {
            return $match;
        }
        return [];
    }

    public function matches($pattern) {
        if (preg_match_all($pattern, $this->data, $matches, PREG_SET_ORDER)) {
            return $matches;
        }
        return [];
    }

    public function toJson() {
        if (is_string($this->data)) {
            $this->data = JsonExpand::decode($this->data);
        }
        return $this;
    }

    public function toXml() {
        $this->data = XmlExpand::decode($this->data);
        return $this->data;
    }

    public function toText() {
        $this->data = HtmlExpand::toText($this->data);
        return $this;
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