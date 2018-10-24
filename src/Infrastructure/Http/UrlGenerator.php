<?php
declare(strict_types = 1);

namespace Zodream\Infrastructure\Http;

use Zodream\Helpers\Str;
use Zodream\Http\Uri;

class UrlGenerator {

    /**
     * @var string
     */
    protected $schema;
    /**
     * @var string
     */
    protected $host;
    /**
     * @var string
     */
    protected $modulePath = '';

    /**
     * @var Request
     */
    protected $request;

    public function __construct() {
        $this->setRequest(app('request'));
        $uri = $this->request->uri();
        //$host = config('app.host');
        $this->setHost($uri->getHost());
        $this->setSchema($uri->getScheme());
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request) {
        $this->request = $request;
    }

    /**
     *
     * @param string $host
     */
    public function setHost($host) {
        $this->host = $host;
//        $real_host = $this->request->uri()->getHost();
//        if ($host == '*' || empty($host)) {
//            $this->host = $real_host;
//            return;
//        }
//        if (!is_array($host)) {
//            $this->host = $host;
//            return;
//        }
//        if (in_array($real_host, $host)) {
//            $this->host = $real_host;
//            return;
//        }
//        $this->host = reset($host);
    }

    /**
     * @param string $modulePath
     */
    public function setModulePath(string $modulePath) {
        $this->modulePath = $modulePath;
    }

    /**
     * @param string $schema
     */
    public function setSchema(string $schema) {
        $this->schema = $schema;
    }

    /**
     * @return string
     */
    public function getHost(): string {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getModulePath(): string {
        return $this->modulePath;
    }

    /**
     * @return string
     */
    public function getSchema(): string {
        return $this->schema;
    }

    /**
     * Get the full URL for the current request.
     *
     * @return string
     */
    public function full(): string {
        return (string)$this->request->uri();
    }

    /**
     * Get the current URL for the request.
     *
     * @return string
     */
    public function current(): string {
        return (string)$this->to($this->request->uri()->getPath());
    }

    /**
     * Get the URL for the previous request.
     *
     * @param  mixed  $fallback
     * @return string
     */
    public function previous($fallback = false): string {
        $referrer = $this->request->referrer();
        if ($referrer) {
            return $referrer;
        }
        if ($fallback) {
            return (string)$this->to($fallback);
        }
        return (string)$this->to('/');
    }

    /**
     * 拼接网址
     * @param null $path
     * @param null $extra
     * @param bool $complete
     * @return string
     */
    public function to($path = null, $extra = null, $complete = true): string {
        if (is_string($path) && ($this->isSpecialUrl($path) || $this->isValidUrl($path))) {
            return $path;
        }
        return (string)$this->toUri($path, $extra, $complete);
    }

    /**
     * @param string|Uri $path
     * @param array|string $extra
     * @param bool $complete
     * @return Uri
     */
    public function toUri($path, $extra = null, $complete = true): Uri {
        if (!$path instanceof Uri) {
            $path = $this->createUri($path);
        }
        if (is_bool($extra)) {
            $complete = $extra;
            $extra = null;
        }
        if (!empty($extra)) {
            $path->addData($extra);
        }
        if ($complete && empty($path->getHost())) {
            $path->setScheme($this->getSchema())
                ->setHost($this->getHost());
        }
        if (!$complete) {
            $path->setHost(null);
        }
        return $path;
    }

    /**
     * CREATE URI BY STRING OR ARRAY
     * @param array|string $file
     * @return Uri
     */
    public function createUri($file) {
        $uri = new Uri();
        if (!is_array($file)) {
            return $uri->decode($this->getPath($file));
        }
        $path = false;
        $data = [];
        foreach ($file as $key => $item) {
            if (is_integer($key)) {
                $path = $item;
                continue;
            }
            $data[$key] = (string)$item;
        }
        if ($path === false) {
            return (clone $this->request->uri())->addData($data);
        }
        return $uri->decode($this->addScript($this->getPath($path)))
            ->addData($data);
    }

    protected function getPath($path): string {
        if (empty($path) || $path === '0') {
            return $this->full();
        }
        if ($path === -1 || $path === '-1') {
            return $this->previous();
        }
        if (!empty(parse_url($path, PHP_URL_HOST))) {
            return $path;
        }
        if (strpos($path, '//') !== false) {
            $path = preg_replace('#/+#', '/', $path);
        }
        if (strpos($path, './') === 0) {
            return $this->addModulePath(substr($path, 2));
        }
        return $path;
    }

    /**
     * 添加当前模块路径
     * @param $path
     * @return string
     */
    protected function addModulePath(string $path): string {
        if (empty($this->modulePath)) {
            return $path;
        }
        return $this->modulePath .'/'.$path;
    }

    protected function addScript($path) {
        if (strpos($path, '.') > 0
            || strpos($path, '/') === 0) {
            return $path;
        }
        $name = $this->request->script();
        if ($name === '/index.php') {
            return '/'.$path;
        }
        return $name.'/'.$path;
    }



    /**判断是否带url段
     * @param string $search
     * @return bool
     */
    public function hasUri($search = null) {
        $url = $this->request->uri()->getPath();
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
    public function isUrl($url) {
        return trim($this->request->uri()->getPath(), '/') == trim($url, '/');
    }

    /**
     * 获取根网址
     *
     * @param boolean $withScript 是否带执行脚本文件
     * @return string
     */
    public function getRoot($withScript = TRUE) {
        $root = $this->getSchema(). '://'.$this->getHost() . '/';
        $self = $this->request->script();
        if ($self !== '/index.php' && $withScript) {
            $root .= ltrim($self, '/');
        }
        return $root;
    }

    /**
     * 获取网址中的虚拟路径
     * @return string
     */
    public function getVirtualUri() {
        $path = $this->request->server('PATH_INFO');
        if (!empty($path)) {
            // 在nginx 下虚拟路径无法获取
            return $path;
        }
        $script = $this->request->script();
        $scriptFile = basename($script);
        $path = $this->request->uri()->getPath();
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

    public function asset(string $path): string {
        if ($this->isValidUrl($path)) {
            return $path;
        }
        return sprintf('%s://%s/%s', $this->getSchema(), $this->getHost(), trim($path, '/'));
    }

    protected function removeIndex(string $root): string {
        $i = 'index.php';
        return Str::contains($root, $i) ? str_replace('/'.$i, '', $root) : $root;
    }

    public function isValidUrl(string $path): bool {
        if (! Str::startsWith($path, ['#', '//', 'mailto:', 'tel:', 'http://', 'https://'])) {
            return filter_var($path, FILTER_VALIDATE_URL) !== false;
        }
        return true;
    }

    public function isSpecialUrl(string $path): bool  {
        return $path == '#' || strpos($path, 'javascript:') === 0;
    }
}