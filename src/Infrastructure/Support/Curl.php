<?php
namespace Zodream\Infrastructure\Support;

use Zodream\Infrastructure\Disk\File;
use Zodream\Infrastructure\Http\Component\Uri;

/**
 * MAKE CURL WITH MY KIND
 * Class Curl
 * @package Zodream\Infrastructure\Http
 */
class Curl {
    /**
     * @var resource
     */
    protected $curl;

    /**
     * @var array
     */
    protected $header;

    protected $result = null;
    public function __construct($url = null) {
        $this->curl = curl_init();
        if (!empty($url)) {
            $this->setUrl($url);
        }
    }

    /**
     * SET URL
     * @param string|Uri $url
     * @param bool $verifySSL
     * @return Curl
     */
    public function setUrl($url, $verifySSL = false) {
        if (!$url instanceof Uri) {
            $url = new Uri($url);
        }
        if (!$verifySSL && $url->isSSL()) {
            $this->setOption(CURLOPT_SSL_VERIFYPEER, FALSE)
                ->setOption(CURLOPT_SSL_VERIFYHOST, FALSE)
                ->setOption(CURLOPT_SSLVERSION, 1);
        }
        return $this->setOption(CURLOPT_URL, (string)$url);
    }



    /**
     * @param string|array $option
     * @param mixed $value
     * @return $this
     */
    public function setOption($option, $value = null) {
        if (is_array($option)) {
            curl_setopt_array($this->curl, $option);
        } else {
            curl_setopt($this->curl, $option, $value);
        }
        return $this;
    }

    /**
     * SET COOKIE FILE
     * @param string|File $file
     * @return $this
     */
    public function setCookieFile($file) {
        $file = (string)$file;
        return $this->setOption(CURLOPT_COOKIEJAR, $file)->setOption(CURLOPT_COOKIEFILE, $file);
    }

    /**
     * SET HEADER
     * @param array $args
     * @return Curl
     */
    public function setHeader(array $args) {
        $header = [];
        foreach ($args as $key => $item) {
            $key = implode('-', array_map('ucfirst', explode('-', strtolower($key))));
            if (empty($item)) {
                continue;
            }
            if (is_array($item)) {
                $item = implode(',', $item);
            }
            $header[] = $key. ':'. $item;
        }
        if (empty($header)) {
            return $this;
        }
        return $this->setOption(CURLOPT_HTTPHEADER, $header);
    }

    /**
     * EXECUTE AND CLOSE
     * @return mixed|null
     * @throws \Exception
     */
    public function execute() {
        $this->result = curl_exec($this->curl);
        $this->header = curl_getinfo($this->curl);
        $this->header['error'] = curl_error($this->curl);
        $this->header['errorNo'] = curl_errno($this->curl);
        if ($this->result === false) {
            throw new \Exception($this->header['error']);
        }
        return $this->result;
    }

    /**
     * GET STATUS
     * @return mixed
     */
    public function getStatus() {
        return $this->header['http_code'];
    }

    /**
     * GET RESULT
     * @return mixed|null
     */
    public function getResult() {
        if (is_resource($this->curl) && is_null($this->result)) {
            return $this->execute();
        }
        return $this->result;
    }

    /**
     * SET COMMON OPTION
     * @return $this
     */
    public function setCommonOption() {
        return $this->setOption(CURLOPT_HEADER, 0)   // 是否输出包含头部
            ->setOption(CURLOPT_RETURNTRANSFER, 1) // 返回不直接输出
            ->setOption(CURLOPT_FOLLOWLOCATION, 1)  // 允许重定向
            ->setOption(CURLOPT_AUTOREFERER, 1);  // 自动设置 referrer
    }

    /**
     * SET USER AGENT
     * @param string $args
     * @return Curl
     */
    public function setUserAgent($args) {
        return $this->setOption(CURLOPT_USERAGENT, $args);
    }

    /**
     * SET REFERRER URL
     * @param string|Uri $url
     * @return Curl
     */
    public function setReferrer($url) {
        return $this->setOption(CURLOPT_REFERER, (string)$url);
    }

    /**
     * NOT OUTPUT BODY
     * @return Curl
     */
    public function setNoBody() {
        return $this->setOption(CURLOPT_NOBODY, 1);
    }

    /**
     * SET COOKIE
     * @param string|array $cookie
     * @return Curl
     */
    public function setCookie($cookie) {
        if (empty($cookie)) {
            return $this;
        }
        if (is_array($cookie)) {
            $cookie = http_build_query($cookie);
        }
        return $this->setOption(CURLOPT_COOKIE, $cookie);
    }

    /**
     * GET METHOD
     * @return string
     */
    public function get() {
        return $this->setCommonOption()->execute();
    }

    /**
     * POST METHOD
     * @param array $data
     * @return mixed|null
     */
    public function post($data = array()) {
        if (!is_string($data)) {
            $data = http_build_query($data);
        }
        return $this->setOption(CURLOPT_POST, 1)
            ->setOption(CURLOPT_POSTFIELDS, $data)
            ->execute();
    }

    /**
     * PUT METHOD
     * @return mixed|null
     */
    public function put() {
        return $this->setOption(CURLOPT_CUSTOMREQUEST, 'PUT')->execute();
    }

    /**
     * PATCH METHOD
     * @param array $data
     * @return mixed|null
     */
    public function patch($data = array()) {
        return $this->setOption(CURLOPT_CUSTOMREQUEST, 'PATCH')
            ->setOption(CURLOPT_POSTFIELDS, $data)
            ->execute();
    }

    /**
     * DELETE METHOD
     * @return mixed|null
     */
    public function delete() {
        return $this->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE')->execute();
    }

    /**
     * DOWNLOAD FILE
     * @param string|File $file
     * @return null
     */
    public function download($file) {
        if (!$file instanceof File) {
            $file = new File($file);
        }
        $fp = fopen((string)$file, 'w');
        $this->setCommonOption()
            ->setOption(CURLOPT_FILE, $fp)
            ->execute();
        fclose($fp);
        return $this->result;
    }

    /**
     * CLOSE
     * @return $this
     */
    public function close() {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
        return $this;
    }

    /**
     * GET ERROR
     * @return string
     */
    public function getError() {
        return $this->header['error'];
    }

    /**
     * GET ERROR NO
     * @return int
     */
    public function getErrorNo() {
        return $this->header['errorNo'];
    }

    public function __destruct() {
        if (is_resource($this->curl)) {
            $this->close();
        }
    }

    public function __toString() {
        return $this->get();
    }
}