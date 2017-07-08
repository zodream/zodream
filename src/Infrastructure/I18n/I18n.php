<?php
namespace Zodream\Infrastructure\I18n;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/25
 * Time: 17:15
 */
use Zodream\Service\Config;
use Zodream\Infrastructure\Disk\Directory;
use Zodream\Infrastructure\Base\MagicObject;
use Zodream\Infrastructure\Http\Request;
use Zodream\Service\Factory;

abstract class I18n extends MagicObject {

    protected $fileName = 'zodream';

    protected $language = 'zh-cn';

    /**
     * @var Directory
     */
    protected $directory;

    public function __construct() {
        $configs = Config::i18n();
        $this->setDirectory($configs['directory']);
        $this->setLanguage($configs['language']);
    }


    /**
     * SET LANGUAGE DIRECTORY
     * @param string|Directory $directory
     * @return $this
     */
    public function setDirectory($directory) {
        if (!$directory instanceof Directory) {
            $directory = Factory::root()->childDirectory($directory);
        }
        $this->directory = $directory;
        return $this;
    }

    /**
     * SET FILE NAME
     * @param string $arg
     * @return $this
     */
    public function setFileName($arg) {
        $this->fileName = $arg;
        return $this;
    }

    /**
     * 设置应用程序语言包
     * @param string $arg 语言
     * @return $this
     */
    public function setLanguage($arg = null) {
        if (empty($arg)) {
            $language = Request::server('HTTP_ACCEPT_LANGUAGE', 'ZH-CN');
            preg_match('/[\w-]+/', $language, $match);
            $arg = $match[0];
        }
        $this->language = strtolower($arg);
        return $this;
    }

    /**
     * 获取语言类型
     *
     * @return string 返回语言,
     */
    public function getLanguage() {
        return $this->language;
    }

    public function translate($message, $param = [], $name = null) {
        if (!is_null($name) && $name != $this->fileName) {
            $this->fileName = $name;
            $this->reset();
        }
    }

    public function format($message, $param = []) {
        if ($param == []) {
            return $message;
        }
        $args = [];
        foreach ((array)$param as $key => $item) {
            $args['{'.$key.'}'] = $item;
        }
        // 替换
        return strtr($message, $args);
    }

    /**
     * 修改源
     */
    abstract public function reset();
}