<?php
namespace Zodream\Domain\Support;

use Zodream\Infrastructure\Disk\File;
class Uploader {

    /**
     * @var File
     */
    protected $file;

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

    public function __construct($file) {
        $this->setFile($file);
    }

    public function setFile($file) {
        $this->file = $file;
    }

    public function save() {

    }

    public function getRandomName($template = null) {
        $randNum = rand(1, 1000000000) .''. rand(1, 1000000000); //如果是32位PHP ，PHP_INT_MAX 有限制报错 int 变为 float
        if (empty($template)) {
            return date('YmdHis').'_'.$randNum.'.'.$this->type;
        }
        //替换日期事件
        $args = explode('-', date('Y-y-m-d-H-i-s'));
        $args[] = time();
        //过滤文件名的非法自负,并替换文件名
        $fileName = substr($this->name, 0, strrpos($this->name, '.'));
        $args[] = preg_replace('/[\|\?\'\<\>\/\*\\\\]+/', '', $fileName);
        $name = str_replace([
            '{yyyy}',
            '{yy}',
            '{mm}',
            '{dd}',
            '{hh}',
            '{ii}',
            '{ss}',
            '{time}',
            '{filename}'
        ], $args, $template);
        //替换随机字符串
        if (preg_match('/\{rand\:([\d]*)\}/i', $name, $matches)) {
            $name = preg_replace('/\{rand\:[\d]*\}/i', substr($randNum, 0, $matches[1]), $name);
        }
        return $name . '.'. $this->type;
    }

    /**
     * 判断类型
     * @param array $args 不包含 .
     * @param bool $allow 是否是检测允许的类型
     * @return bool
     */
    public function checkType(array $args = [], $allow = true) {
        return in_array($this->type, $args)  === $allow;
    }

    /**
     * 验证大小
     * @param int $min
     * @param int $max
     * @return bool
     */
    public function checkSize($min = 10000000, $max = null) {
        if (is_null($max)) {
            $max = $min;
            $min = 0;
        }
        if ($min > $max) {
            return $this->size >= $max && $this->size <= $min;
        }
        return $this->size <= $max && $this->size >= $min;
    }

    /**
     * 验证文件夹
     * @return bool
     */
    public function checkDirectory() {
        $directory = $this->file->getDirectory();
        if (!$directory->exist() && !$directory->create()) {
            $this->setError('ERROR_CREATE_DIR');
            return false;
        }
        if (!$this->file->canWrite()) {
            $this->setError('ERROR_DIR_NOT_WRITEABLE');
            return false;
        }
        return true;
    }
}