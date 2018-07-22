<?php
declare(strict_types = 1);

namespace Zodream\Infrastructure\Http;

use Zodream\Http\Uri;
use Zodream\Service\Config;

class URL {
    private static $_host;

    /***
     * @var string 模块的路径
     */
    private static $_module_path = '';

    /**
     * @param string $module_path
     */
    public static function setModulePath($module_path) {
        self::$_module_path = $module_path;
    }

    /**
     * @return string
     */
    public static function getModulePath() {
        return self::$_module_path;
    }

    public static function setHost($host = null) {
        if (!empty($host)) {
            self::$_host = $host;
            return;
        }
        if (Config::isDebug()) {
            self::$_host = app('request')->uri()->getHost();
            return;
        }
        self::$_host = config('app.host') ?: app('request')->uri()->getHost();
    }

    /**
     * 获取host 包括域名和端口 80 隐藏
     * @return string
     */
    public static function getHost() {
        if (empty(self::$_host)) {

            // 出现配置循环 bug
            static::setHost();
        }
        return self::$_host;
    }

    /**
     * 产生网址
     * @param string|array|Uri $file
     * @param array|string|bool $extra
     * @param bool $complete
     * @return string|Uri
     */
    public static function to($file = null, $extra = null, $complete = true) {
        if (is_string($file) &&
            ($file === '#'
                || strpos($file, 'javascript:') === 0)) {
            return $file;
        }
        if (!$file instanceof Uri) {
            $file = static::createUri($file);
        }
        if (is_bool($extra)) {
            $complete = $extra;
            $extra = null;
        }
        if (!empty($extra)) {
            $file->addData($extra);
        }
        if ($complete && empty($file->getHost())) {
            $file->setScheme(app('request')->uri()->getScheme())
                ->setHost(self::getHost());
        }
        return $file;
    }

    /**
     * CREATE URI BY STRING OR ARRAY
     * @param array|string $file
     * @return Uri
     */
    public static function createUri($file) {
        $uri = new Uri();
        if (!is_array($file)) {
            return $uri->decode(static::getPath($file));
        }
        $path = null;
        $data = array();
        foreach ($file as $key => $item) {
            if (is_integer($key)) {
                $path = $item;
                continue;
            }
            $data[$key] = (string)$item;
        }
        return $uri->decode(static::addScript(static::getPath($path)))
            ->addData($data);
    }

    protected static function getPath($path) {
        if (empty($path) || $path === '0') {
            return app('request')->uri()->encode(false);
        }
        if ($path === -1 || $path === '-1') {
            return app('request')->referrer();
        }
        if (!empty(parse_url($path, PHP_URL_HOST))) {
            return $path;
        }
        if (strpos($path, '//') !== false) {
            $path = preg_replace('#/+#', '/', $path);
        }
        if (strpos($path, './') === 0) {
            return static::addModulePath(substr($path, 2));
        }
        return $path;
    }

    /**
     * 添加当前模块路径
     * @param $path
     * @return string
     */
    protected static function addModulePath($path) {
        if (empty(self::$_module_path)) {
            return $path;
        }
        return self::$_module_path .'/'.$path;
    }

    protected static function addScript($path) {
        if (strpos($path, '.') > 0
            || strpos($path, '/') === 0) {
            return $path;
        }
        $name = app('request')->script();
        if ($name === '/index.php') {
            return '/'.$path;
        }
        return $name.'/'.$path;
    }



    /**判断是否带url段
     * @param string $search
     * @return bool
     */
    public static function hasUri($search = null) {
        $url = app('request')->uri()->getPath();
        if (is_null($search) && $url == '/') {
            return true;
        }
        return strpos($url, '/'.trim($search, '/')) !== false;
    }

    /**
     * 判断是否是url
     * @param string $url
     * @return bool
     */
    public static function isUrl($url) {
        return trim(app('request')->uri()->getPath(), '/') == trim($url, '/');
    }

    /**
     * 获取根网址
     *
     * @param boolean $withScript 是否带执行脚本文件
     * @return string
     */
    public static function getRoot($withScript = TRUE) {
        $root = app('request')->uri()->getScheme(). '://'.static::getHost() . '/';
        $self = app('request')->script();
        if ($self !== '/index.php' && $withScript) {
            $root .= ltrim($self, '/');
        }
        return $root;
    }

    /**
     * 获取网址中的虚拟路径
     * @return string
     */
    public static function getVirtualUri() {
        $path = app('request')->server('PATH_INFO');
        if (!empty($path)) {
            // 在nginx 下虚拟路径无法获取
            return $path;
        }
        $script = app('request')->script();
        $scriptFile = basename($script);
        $path = app('request')->uri()->getPath();
        if (strpos($scriptFile, $path) === 0) {
            $path = rtrim($path, '/'). '/'. $scriptFile;
        } elseif (strpos($script, '.php') > 0) {
            $script = preg_replace('#/[^/]+\.php$#i', '', $script);
        }
        // 判断是否是二级文件默认入口
        if (!empty($script) && strpos($path, $script) === 0) {
            return substr($path, strlen($script));
        }
        // 判断是否是根目录其他文件入口
        if (strpos($path, $scriptFile) === 1) {
            return '/'.substr($path, strlen($scriptFile) + 1);
        }
        return $path;
    }
}