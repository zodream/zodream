<?php
namespace Zodream\Domain\Upload;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/28
 * Time: 18:48
 */
use Zodream\Infrastructure\Http\Request;

class UploadInput extends BaseUpload {

    public function __construct() {
        $this->name = Request::server('HTTP_X_FILENAME');
        $this->setType();
    }

    /**
     * 保存到指定路径
     * @return bool
     */
    public function save() {
        if (!parent::save()) {
            return false;
        }
        if (!$fileOpen = @fopen('php://input', 'rb')) {
            $this->setError('INPUT_ERROR');
            return false;
        }
        if (!$fileOutput = @fopen($this->file->getFullName(), 'wb')) {
            $this->setError('WRITE_ERROR');
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