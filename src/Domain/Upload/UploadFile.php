<?php
namespace Zodream\Domain\Upload;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/28
 * Time: 10:59
 */
class UploadFile extends BaseUpload {

    protected $tempName;

    protected $mineType;

    protected $errorMap = [
        null,
        'UPLOAD_ERR_INI_SIZE',
        'UPLOAD_ERR_FORM_SIZE',
        'UPLOAD_ERR_PARTIAL',
        'NO FILE',
        'FILE IS NULL',
        'UPLOAD_ERR_NO_TMP_DIR',
        'UPLOAD_ERR_CANT_WRITE',
        'UPLOAD_ERR_EXTENSION'
    ];

    public function __construct(array $args = null) {
        if (!is_null($args)) {
            $this->load($args);
        }
    }

    public function load($name, $tempName = null, $size = 0, $type = '', $error = 0) {
        if (empty($name)) {
            return;
        }
        if (is_array($name)) {
            $size = $name['size'];
            $tempName = $name['tmp_name'];
            $error = $name['error'];
            $type = $name['type'];
            $name = $name['name'];
        }
        $this->name = $name;
        $this->tempName = $tempName;
        $this->size = $size;
        $this->mineType = $type;
        $this->error = $error;
        $this->setType();
    }

    /**
     * 验证尺寸
     * @param callable $cb
     * @return bool
     */
    public function validateDimensions(callable $cb) {
        if (in_array($this->mineType, ['image/svg+xml', 'image/svg'])) {
            return true;
        }
        if (! $sizeDetails = @getimagesize($this->tempName)) {
            return false;
        }
        [$width, $height] = $sizeDetails;
        // TODO 验证图片的最小宽高
        return call_user_func($cb, $width, $height);
    }


    /**
     * 保存到指定路径
     * @return bool
     */
    public function save() {
        if (!parent::save()) {
            return false;
        }
        if (!move_uploaded_file($this->tempName, $this->file->getFullName()) ||
            !$this->file->exist()) {
            $this->setError(
                __('ERROR_FILE_MOVE')
            );
            return false;
        }
        return true;
    }
}