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
    protected array $__attributes = [];

    /**
     * @var Directory|null
     */
    protected ?Directory $directory = null;

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
        if (!array_key_exists($key, $this->__attributes)) {
            return $default;
        }
        return $this->__attributes[$key];
    }

    public function upload(string $key) {
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
                $files['type'][$i],
                $files['error'][$i]
            );
        }
        return $this;
    }

    public function addFile($file) {
        if (func_num_args() > 3) {
            $upload = new UploadFile();
            call_user_func_array([$upload, 'load'], func_get_args());
            $this->__attributes[] = $upload;
            return $upload;
        }
        if ($file instanceof BaseUpload) {
            $this->__attributes[] = $file;
            return $file;
        }
        if (is_array($file)) {
            $upload = new UploadFile($file);
            $this->__attributes[] = $upload;
            return $upload;
        }
        if (!is_string($file)) {
            return false;
        }
        return $this->upload($file);
    }

    /**
     * 循环遍历
     * @param callable $callback
     * @param int $count 限制遍历的数量
     * @return $this
     */
    public function each(callable $callback, int $count = 0) {
        $items = $this->__attributes;
        $length = count($items);
        $length = $count > 0 && $count > $length ? $count : $length;
        foreach ($items as $item) {
            $length --;
            call_user_func($callback, $item);
            if ($length < 1) {
                break;
            }
        }
        return $this;
    }
    
    public function save(): bool {
        $result = true;
        foreach ($this->__attributes as $item) {
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
    public function checkType(array|string $args, bool $allow = true): bool {
        $result = true;
        foreach ($this->__attributes as $item) {
            if (!$item->checkType((array)$args, $allow)) {
                $result = false;
            }
        }
        return $result;
    }

    public function checkSize(int $min = 10000000, int $max = -1): bool {
        $result = true;
        foreach ($this->__attributes as $item) {
            if (!$item->checkSize($min, $max)) {
                $result = false;
            }
        }
        return $result;
    }

    public function validateDimensions(callable $cb = null): bool {
        $result = true;
        foreach ($this->__attributes as $item) {
            if (!$item->validateDimensions($cb)) {
                $result = false;
            }
        }
        return $result;
    }
    
    public function saveOne($file, int $index = 0) {
        return $this->__attributes[$index]
            ->setFile($file)
            ->save();
    }

    public function getError(int $index = null) {
        if (!is_null($index)) {
            return $this->__attributes[$index]->getError();
        }
        $args = [];
        foreach ($this->__attributes as $item) {
            $error = $item->getError();
            if (!empty($error)) {
                $args[] = $error;
            }
        }
        return $args;
    }
}