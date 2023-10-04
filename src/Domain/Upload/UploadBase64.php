<?php
declare(strict_types=1);
namespace Zodream\Domain\Upload;

use Zodream\Http\MIME;

class UploadBase64 extends BaseUpload {

    protected string $mineType = '';
    protected string $content = '';

    public function __construct(string $data = '') {
        $this->load($data);
    }

    public function load(string $data = ''): void {
        if (preg_match('/^(data:\s*(\w+?);base64,)/', $data, $result)){
            $this->mineType = $result[2];
            $this->setType(MIME::extension($this->mineType));
            $data = substr($data, strlen($result[1]));
        }
        $this->content = base64_decode($data);
        $this->size = strlen($this->content);
    }

    public function setType(string $type = ''): static {
        $this->type = ltrim($type, '.');
        return $this;
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