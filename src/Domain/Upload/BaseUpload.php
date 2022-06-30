<?php
declare(strict_types=1);
namespace Zodream\Domain\Upload;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/28
 * Time: 14:17
 */

use Exception;
use Zodream\Disk\FileSystem;
use Zodream\Infrastructure\Base\ConfigObject;
use Zodream\Disk\File;
 
abstract class BaseUpload extends ConfigObject {

    protected string $configKey = 'upload';

    protected array $configs = [
        'allowType' => ['png', 'jpg', 'jpeg', 'bmp', 'gif'],
        'maxSize' => 2000000
    ];

    protected string $name = '';

    protected string $type = '';

    protected int $size = -1;

    /**
     * @var File|null
     */
    protected ?File $file = null;
    
    protected string|int $error = '';
    
    protected array $errorMap = [];

    public function setError(string|int $error = 0) {
        if (empty($error)) {
            return $this;
        }
        if (!is_numeric($error)) {
            $this->error = $error;
            return $this;
        }
        $this->error = $this->errorMap[$error] ?? $error;
        return $this;
    }

    /**
     * 获取保存后的路径
     * @return File|null
     */
    public function getFile() {
        return $this->file;
    }

    public function setFile(string|File $file) {
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
    
    public function setType(string $type = '') {
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
        if ($this->size < 0) {
            $this->size = $this->getFile()->size();
        }
        return $this->size;
    }
    

    /**
     * 保存到指定路径
     * @return bool
     */
    public function save(): bool {
        return $this->checkDirectory();
    }

    public function getRandomName(string $template = ''): string {
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
            $name = preg_replace('/\{rand\:[\d]*\}/i', substr($randNum, 0, intval($matches[1])), $name);
        }
        return $name . '.'. $this->type;
    }

    /**
     * 判断类型
     * @param array $args 不包含 .
     * @param bool $allow 是否是检测允许的类型
     * @return bool
     */
    public function checkType(array $args = [], bool $allow = true): bool {
        return in_array($this->type, $args)  === $allow;
    }

    /**
     * 验证大小
     * @param int $min
     * @param int $max
     * @return bool
     */
    public function checkSize(int $min = 10000000, int $max = -1): bool {
        if ($max < 0) {
            $max = $min;
            $min = 0;
        }
        if ($min > $max) {
            return $this->size >= $max && $this->size <= $min;
        }
        return $this->size <= $max && $this->size >= $min;
    }

    public function validateDimensions(callable $cb = null): bool {
        return true;
    }

    /**
     * 验证文件夹
     * @return bool
     * @throws Exception
     */
    public function checkDirectory(): bool {
        $directory = $this->file->getDirectory();
        if (!$directory->create()) {
            $this->setError(
                __('ERROR_CREATE_DIR')
            );
            return false;
        }
        if ($this->file->exist() && !$this->file->canWrite()) {
            $this->setError(
                __( 'ERROR_DIR_NOT_WRITEABLE' )
            );
            return false;
        }
        return true;
    }
}