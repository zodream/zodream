<?php
namespace Zodream\Infrastructure\Support;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/17
 * Time: 21:28
 */
use Zodream\Infrastructure\Base\MagicObject;
use Zodream\Infrastructure\Disk\Directory;
use Zodream\Infrastructure\Disk\File;

class Template extends  MagicObject {
    /**
     * @var Directory
     */
    protected $directory;

    protected $beginTag = '{';

    protected $endTag = '}';

    public function __construct($directory = null) {
        if (!empty($directory)) {
            $this->setDirectory($directory);
        }
    }

    /**
     * 设置基路径
     * @param $directory
     * @return $this
     */
    public function setDirectory($directory) {
        $this->directory = $directory instanceof Directory ?
            $directory : new Directory($directory);
        return $this;
    }

    /**
     * 设置标志
     * @param string $beginTag
     * @param string $endTag
     * @return $this
     */
    public function setTag($beginTag = '{', $endTag = '}') {
        $this->beginTag = $beginTag;
        $this->endTag = $endTag;
        return $this;
    }

    /**
     * 根据路径获取替换后的内容
     * @param string $file
     * @return bool|mixed
     */
    public function getText($file) {
        $file = is_file($file) ? new File($file) : $this->directory->childFile($file);
        if (!$file->exist()) {
            return false;
        }
        return $this->replaceByArray($file->read(), $this->get());
    }

    /**
     * 根据关联数组替换内容
     * @param string $content
     * @param array $data
     * @return string
     */
    public function replaceByArray($content, array $data) {
        /*foreach ($data as $key => $item) {
            if (is_array($item)) {
                $content = $this->replaceByArray($content, $item, $pre.$key.'.');
            } else {
                $content = $this->replace($content, $pre.$key, $item);
            }
        }*/
        $keys = [];
        $values = [];
        foreach ($data as $key => $value) {
            $keys[] = $this->beginTag.$key.$this->endTag;
            $values[] = is_array($value) ? var_export($value, true) : $value;
        }
        return str_ireplace($keys, $values, $content);
    }

    /**
     * 替换单个
     * @param string $content
     * @param string $tag
     * @param string $value
     * @return string
     */
    public function replace($content, $tag, $value) {
        return str_ireplace($this->beginTag.$tag.$this->endTag, $value, $content);
    }
}