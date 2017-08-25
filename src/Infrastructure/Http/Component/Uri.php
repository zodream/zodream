<?php
namespace Zodream\Infrastructure\Http\Component;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/6
 * Time: 10:07
 */

class Uri {

    protected $scheme = 'http';

    protected $host;

    protected $port = 80;

    protected $username;

    protected $password;

    protected $path = null;

    protected $data = array();

    protected $fragment;

    public function __construct($url = null) {
        if (!empty($url)) {
            $this->decode($url);
        }
    }

    /**
     * @param string $arg
     * @return $this
     */
    public function setScheme($arg) {
        $this->scheme = $arg;
        return $this;
    }

    /**
     * @return string
     */
    public function getScheme() {
        return $this->scheme;
    }

    /**
     * @return bool
     */
    public function isSSL() {
        return 'https' == $this->scheme;
    }

    /**
     * @param string $arg
     * @return $this
     */
    public function setHost($arg) {
        $args = explode(':', $arg);
        $this->host = $args[0];
        if (count($args) > 1) {
            $this->setPort($args[1]);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param int $arg
     * @return $this
     */
    public function setPort($arg = 80) {
        $this->port = intval($arg);
        return $this;
    }

    /**
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @param string $arg
     * @return $this
     */
    public function setUsername($arg) {
        $this->username = $arg;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * @param string $arg
     * @return $this
     */
    public function setPassword($arg) {
        $this->password = $arg;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @param string $arg
     * @return $this
     */
    public function setPath($arg) {
        $this->path = $arg;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * @param string|array $arg
     * @return $this
     */
    public function setData($arg) {
        if (empty($arg)) {
            return $this;
        }
        if (is_string($arg)) {
            $str = str_replace('&amp;', '&', $arg);
            $arg = array();
            parse_str($str, $arg);
        }
        $this->data = $arg;
        return $this;
    }

    /**
     * @param string|array $key
     * @param string $value
     * @return $this
     */
    public function addData($key, $value = null) {
        if (empty($key)) {
            return $this;
        }
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
            return $this;
        }
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return array|bool
     */
    public function getData($key = null) {
        if (is_null($key)) {
            return $this->data;
        }
        return $this->data[$key] ?: false;
    }

    /**
     * @param string $arg
     * @return $this
     */
    public function setFragment($arg) {
        $this->fragment = $arg;
        return $this;
    }

    /**
     * @param string $arg
     * @return $this
     */
    public function addFragment($arg) {
        if (empty($this->fragment)) {
            return $this->setFragment($arg);
        }
        $this->fragment .= '&'.$arg;
        return $this;
    }

    /**
     * ID
     * @return string
     */
    public function getFragment() {
        return $this->fragment;
    }

    /**
     * STRING TO
     * @param $url
     * @return $this
     */
    public function decode($url) {
        $args = parse_url($url);
        if (isset($args['scheme'])) {
            $this->scheme = $args['scheme'];
        }

        if (isset($args['host'])) {
            $this->host = $args['host'];
        }

        if (isset($args['port'])) {
            $this->port = $args['port'];
        }

        if (isset($args['user'])) {
            $this->username = $args['user'];
        }

        if (isset($args['pass'])) {
            $this->password = $args['pass'];
        }

        if (isset($args['path'])) {
            $this->path = $args['path'];
        }

        if (isset($args['query'])) {
            $this->setData($args['query']);
        }

        if (isset($args['fragment'])) {
            $this->fragment = $args['fragment'];
        }
        return $this;
    }

    /**
     * TO STRING
     * @param bool $hasRoot
     * @return string
     */
    public function encode($hasRoot = true) {
        $arg = $hasRoot && !empty($this->host) ? $this->getRoot() : null;
        $arg .= '/'.ltrim($this->path, '/');
        if (!empty($this->data)) {
            $arg .= '?'. http_build_query($this->data);
        }
        if (!empty($this->fragment)) {
            return $arg.'#'.$this->fragment;
        }
        return $arg;
    }

    /**
     * GET URL ROOT
     * @return string
     */
    public function getRoot() {
        $arg = $this->scheme.'://';
        if (!empty($this->username) && !empty($this->password)) {
            $arg .= $this->username.':'.$this->password.'@';
        }
        $arg .= $this->host;
        if (!empty($this->port) && $this->port != 80) {
            return $arg. ':'. $this->port;
        }
        return $arg;
    }

    public function __toString() {
        return $this->encode();
    }

}