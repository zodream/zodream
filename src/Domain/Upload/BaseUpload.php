<?php
namespace Zodream\Domain\Upload;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/28
 * Time: 14:17
 */
 use Zodream\Infrastructure\Base\ConfigObject;
 use Zodream\Infrastructure\Disk\File;
 use Zodream\Infrastructure\FileSystem;
 
abstract class BaseUpload extends ConfigObject {

    protected $configKey = 'upload';

    protected $configs = [
        'allowType' => ['png', 'jpg', 'jpeg', 'bmp', 'gif'],
        'maxSize' => 2000000
    ];

    protected $name;

    protected $type;

    protected $size;

    /**
     * @var File
     */
    protected $file;
    
    protected $error = null;
    
    protected $errorMap = [];

    public function setError($error = 0) {
        if (empty($error)) {
            return $this;
        }
        if (!is_numeric($error)) {
            $this->error = $error;
        }
        $this->error = $this->errorMap[$error] || $error;
        return $this;
    }

    /**
     * 获取保存后的路径
     * @return File
     */
    public function getFile() {
        return $this->file;
    }

    public function setFile($file) {
        if ($file instanceof File) {
            $file = new File($file);
        }
        $this->file = $file;
        return $this;
    }

    public function getError() {
        return $this->error;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function setType($type = null) {
        if (empty($type)) {
            $type = FileSystem::getExtension($this->name);
        }
        $this->type = $type;
        return $this;
    }
    
    public function getType() {
        if (empty($this->type)) {
            $this->setType();
        }
        return $this->type;
    }
    
    public function getSize() {
        return $this->size;
    }
    

    /**
     * 保存到指定路径
     * @return bool
     */
    public function save() {
        return $this->checkDirectory();
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