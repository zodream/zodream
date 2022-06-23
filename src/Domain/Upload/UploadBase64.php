<?php
declare(strict_types=1);
namespace Zodream\Domain\Upload;

class UploadBase64 extends BaseUpload {

    protected string $content = '';

    public function __construct(string $data = '') {
        $this->load($data);
    }

    public function load(string $data = '') {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $data, $result)){
            $this->setType($result[2]);
            $data = substr($data, strlen($result[1]));
        }
        $this->content = base64_decode($data);
        $this->size = strlen($this->content);
    }

    public function setType(string $type = '') {
        $this->type = ltrim($type, '.');
    }

    /**
     * 保存到指定路径
     * @return bool
     */
    public function save(): bool {
        if (!parent::save()) {
            return false;
        }
        if (!$this->file->write($this->content) ||
            !$this->file->exist()) { //移动失败
            $this->setError(
                __('ERROR_WRITE_CONTENT')
            );
            return false;
        }
        return true;
    }
}