<?php
declare(strict_types=1);
namespace Zodream\Domain\Upload;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/28
 * Time: 18:48
 */

class UploadInput extends BaseUpload {

    public function __construct() {
        $this->setName(request()->server('HTTP_X_FILENAME'));
    }

    public function setName(string $name): static {
        $this->name = $name;
        $this->setType();
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
        if (!$fileOpen = @fopen('php://input', 'rb')) {
            $this->setError(
                __('INPUT_ERROR')
            );
            return false;
        }
        if (!$fileOutput = @fopen($this->file->getFullName(), 'wb')) {
            $this->setError(
                __('WRITE_ERROR')
            );
            return false;
        }
        while ($buff = fread($fileOpen, 4096)) {
            fwrite($fileOutput, $buff);
        }
        @fclose($fileOutput);
        @fclose($fileOpen);
        $this->size = $this->file->size();
        return true;
    }
}