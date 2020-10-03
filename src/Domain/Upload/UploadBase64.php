<?php
namespace Zodream\Domain\Upload;

use Zodream\Infrastructure\Http\Request;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/28
 * Time: 14:16
 */
class UploadBase64 extends BaseUpload {

    public function __construct($key = null) {
        $this->load($key);
    }

    public function load($key = null) {
        $content = app('request')->request($key);
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $content, $result)){
            $this->setType($result[2]);
            $content = substr($content, strlen($result[1]));
        }
        $this->name = base64_decode($content);
        $this->size = strlen($this->name);
    }

    public function setType($type = '') {
        $this->type = ltrim($type, '.');
    }

    /**
     * 保存到指定路径
     * @return bool
     */
    public function save() {
        if (!parent::save()) {
            return false;
        }
        if (!$this->file->write($this->name) ||
            !$this->file->exist()) { //移动失败
            $this->setError(
                __('ERROR_WRITE_CONTENT')
            );
            return false;
        }
        return true;
    }
}