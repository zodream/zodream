<?php
namespace Zodream\Domain\Support;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/17
 * Time: 9:30
 */
use Zodream\Infrastructure\Disk\File;
use Zodream\Infrastructure\Http\Component\Header;
use Zodream\Infrastructure\Http\Component\Uri;
use Zodream\Infrastructure\Support\Curl;

class Http {

    // 通过请求URI得到资源
    const GET = 'GET';
    // 用于添加新的内容
    const POST = 'POST';
    // 用于修改某个内容
    const PUT = 'PUT';
    // 删除某个内容
    const DELETE = 'DELETE';
    // 用于代理进行传输，如使用SSL
    const CONNECT = 'CONNECT';
    // 询问可以执行哪些方法
    const OPTIONS = 'OPTIONS';
    // 部分文档更改
    const PATCH = 'PATCH';
    // 类似于GET, 但是不返回body信息，用于检查对象是否存在，以及得到对象的元数据
    const HEAD = 'HEAD';
    // 用于远程诊断服务器
    const TRACE = 'TRACE';
    /**
     * @var Uri
     */
    protected $url;

    protected $method = self::GET;

    protected $data = [];

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var Header
     */
    protected $header;

    /**
     * @var File
     */
    protected $cookieFile;

    /**
     * @var array
     */
    protected $cookie = [];

    protected $options = [];

    public function __construct($url = null) {
        if (empty($url)) {
            $this->setUrl($url);
        }
        $this->header = new Header();
    }

    public function getUrl() {
        return $this->url;
    }

    public function setUrl($url) {
        $this->url = $url instanceof Uri ? $url : new Uri($url);
        return $this;
    }

    public function getMethod() {
        return $this->method;
    }

    public function setMethod($method) {
        $this->method = $method;
        return $this;
    }

    public function getData($key = null) {
        if (is_null($key)) {
            return $this->data;
        }
        return array_key_exists($key, $this->data) ? $this->data[$key] : null;
    }

    public function setData($key, $value = null) {
        $this->data = [];
        return $this->addData($key, $value);
    }

    public function addData($key, $value = null) {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
            return $this;
        }
        $this->data[$key] = $value;
        return $this;
    }

    public function getOption($key = null) {
        if (is_null($key)) {
            return $this->options;
        }
        return array_key_exists($key, $this->options) ? $this->options[$key] : null;
    }

    public function setOption($key, $value = null) {
        $this->options = [];
        return $this->addData($key, $value);
    }

    /**
     * SET DEFAULT OPTION
     * @param array $option
     * @return $this
     */
    public function setDefaultOption(array $option) {
        $this->options = $this->options + $option;
        return $this;
    }

    public function addOption($key, $value = null) {
        if (is_array($key)) {
            $this->options = array_merge($this->options, $key);
            return $this;
        }
        $this->options[$key] = $value;
        return $this;
    }

    public function getCookie($key = null) {
        if (is_null($key)) {
            return $this->cookie;
        }
        return array_key_exists($key, $this->cookie) ? $this->cookie[$key] : null;
    }

    public function setCookie($key, $value = null) {
        $this->cookie = [];
        return $this->addCookie($key, $value);
    }

    public function addCookie($key, $value = null) {
        if (is_array($key)) {
            $this->cookie = array_merge($this->cookie, $key);
            return $this;
        }
        $this->cookie[$key] = $value;
        return $this;
    }

    public function getCookieFile() {
        return $this->cookieFile;
    }

    public function setCookieFile($file) {
        $this->cookieFile = $file instanceof File ? $file : new File($file);
        return $this;
    }

    public function getHeader() {
        return $this->header;
    }

    public function setHeader($header) {
        $this->header = $header;
        return $this;
    }

    /**
     * ADD headers
     * @param array $headers
     * @return $this
     */
    public function addHeaders(array $headers) {
        $this->header->add($headers);
        return $this;
    }

    /**
     * CREATE CURL
     * @param bool $isNew
     * @return Curl
     */
    public function request($isNew = true) {
        if (!$this->curl instanceof Curl || $isNew) {
            $this->create();
        }
        $this->curl->setUrl($this->url);
        $this->curl->setHeader($this->header->toArray())
            ->setOption($this->options);
        if (!empty($this->cookie)) {
            $this->curl->setCookie($this->cookie);
        }
        if ($this->cookieFile instanceof File) {
            $this->curl->setCookieFile($this->cookieFile);
        }
        return $this->curl;
    }

    /**
     * CLOSE AND CREATE NEW CURL
     * @return $this
     */
    public function create() {
        if ($this->curl instanceof Curl) {
            $this->curl->close();
        }
        $this->curl = new Curl();
        return $this;
    }
}