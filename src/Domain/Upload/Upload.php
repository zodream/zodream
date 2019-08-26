<?php
namespace Zodream\Domain\Upload;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/28
 * Time: 10:58
 */
use Zodream\Disk\Directory;
use Zodream\Infrastructure\Base\MagicObject;

class Upload extends MagicObject {
    /** @var BaseUpload[] */
    protected $_data = [];

    /**
     * @var Directory
     */
    protected $directory;

    public function setDirectory($directory) {
        if (!$directory instanceof Directory) {
            $directory = new Directory($directory);
        }
        $this->directory = $directory;
        return $this;
    }

    /**
     * 获取
     * @param integer $key
     * @param null $default
     * @return BaseUpload
     */
    public function get($key = 0, $default = null) {
        if (!array_key_exists($key, $this->_data)) {
            return $default;
        }
        return $this->_data[$key];
    }

    public function upload($key) {
        if (!array_key_exists($key, $_FILES)) {
            return false;
        }
        $files = $_FILES[$key];
        if (!is_array($files['name'])) {
            return $this->addFile($files);
        }
        for ($i = 0, $length = count($files['name']); $i < $length; $i ++) {
            $this->addFile(
                $files['name'][$i], 
                $files['tmp_name'][$i], 
                $files['size'][$i], 
                $files['error'][$i]
            );
        }
        return $this;
    }

    public function addFile($file) {
        if (func_num_args() == 4) {
            $upload = new UploadFile();
            call_user_func_array([$upload, 'load'], func_get_args());
            $this->_data[] = $upload;
            return $upload;
        }
        if ($file instanceof BaseUpload) {
            $this->_data[] = $file;
            return $file;
        }
        if (is_array($file)) {
            $upload = new UploadFile($file);
            $this->_data[] = $upload;
            return $upload;
        }
        if (!is_string($file)) {
            return false;
        }
        return $this->upload($file);
    }

    public function each(callable $callback) {
        foreach ($this->_data as $item) {
            call_user_func($callback, $item);
        }
        return $this;
    }
    
    public function save() {
        $result = true;
        foreach ($this->_data as $item) {
            $item->setFile($this->directory->childFile($item->getRandomName()));
            if (!$item->save()) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * @param array|string $args 不带点的拓展名
     * @param bool $allow 是否允许上传
     * @return bool
     */
    public function checkType($args, $allow = true) {
        $result = true;
        foreach ($this->_data as $item) {
            if (!$item->checkType((array)$args, $allow)) {
                $result = false;
            }
        }
        return $result;
    }

    public function checkSize($min = 10000000, $max = null) {
        $result = true;
        foreach ($this->_data as $item) {
            if (!$item->checkSize($min, $max)) {
                $result = false;
            }
        }
        return $result;
    }
    
    public function saveOne($file, $index = 0) {
        return $this->_data[$index]
            ->setFile($file)
            ->save();
    }

    public function getError($index = null) {
        if (!is_null($index)) {
            return $this->_data[$index]->getError();
        }
        $args = [];
        foreach ($this->_data as $item) {
            $args[] = $item->getError();
        }
        return $args;
    }
}