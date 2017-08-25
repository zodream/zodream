<?php
namespace Zodream\Domain\Upload;
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
        $this->name = base64_decode($_POST[$key]);
        $this->size = strlen($this->name);
    }

    public function setType($type) {
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
            $this->setError('ERROR_WRITE_CONTENT');
            return false;
        }
        return true;
    }
}