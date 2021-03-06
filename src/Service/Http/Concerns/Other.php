<?php
declare(strict_types=1);
namespace Zodream\Service\Http\Concerns;

use Zodream\Infrastructure\Support\UserAgent;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/3
 * Time: 9:29
 */
trait Other {


    protected function createUrl(): string {
        return sprintf('%s://%s%s', $this->isSSL() ? 'https' : 'http', $this->host(), $this->createUriPath());
    }

    protected function createPath(): string {
        return parse_url($this->url(), PHP_URL_PATH);
    }

    /**
     * 获取网址
     *
     * @return string 真实显示的网址
     */
    protected function createUriPath() {
        if ($uri = $this->server('REQUEST_URI')) {
            return $uri;
        }
        $self = $this->server('PHP_SELF');
        if ($argv = $this->server('argv')) {
            unset($argv[0]);
            return $self .'?'.implode('&', $argv);
        }
        return $self .'?'. $this->server('QUERY_STRING');
    }

    /**
     * 判断是否SSL协议
     * @return boolean
     */
    protected function createIsSSL() {
        $https = $this->server('HTTPS');
        if ('1' == $https || 'on' == strtolower($https)) {
            return true;
        }
        return $this->server('SERVER_PORT') == 443;
    }

    /**
     * 只支持basic
     * @return array
     */
   protected function createAuth() {
        if ($user = $this->server('PHP_AUTH_USER')) {
            return [$user, $this->server('PHP_AUTH_PW')];
        }
        return [null, null];
    }

    /**
     * @return array
     */
    protected function createBasicToken() {
        $header = $this->header('Authorization');
        if (empty($header)) {
            return [null, null];
        }
        if (is_array($header)) {
            $header = current($header);
        }
        if (strpos($header, 'Basic ') !== 0) {
            return [null, null];
        }
        if (!($decoded = base64_decode(substr($header, 6)))) {
            return [null, null];
        }
        if (strpos($decoded, ':') === false) {
            return [null, null]; // HTTP Basic header without colon isn't valid
        }
        return explode(':', $decoded, 2);
    }

    protected function http_digest_parse($txt) {
        // protect against missing data
        $needed_parts = array(
            'nonce' => 1,
            'nc' => 1,
            'cnonce' => 1,
            'qop' => 1,
            'username' => 1,
            'uri'=> 1,
            'response' => 1);
        $data = array();

        preg_match_all('@(\w+)=([\'"]?)([a-zA-Z0-9=./\_-]+)\2@', $txt, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $data[$m[1]] = $m[3];
            unset($needed_parts[$m[1]]);
        }

        return $needed_parts ? false : $data;
    }

    /**
     * 获取提交的方法
     * @return string
     */
   protected function createMethod() {
        if ($method = $this->server('HTTP_X_HTTP_METHOD_OVERRIDE')) {
            return strtoupper($method);
        }
        return strtoupper($this->server('REQUEST_METHOD') ?: 'GET');
    }

    /**
     * 获取host 和port
     * @return string
     */
   protected function createHost() {
        if ($host = $this->server('HTTP_X_FORWARDED_HOST')) {
            // 防止通过局域网代理取得ip值
            return $host;
        }
        if ($host = $this->server('HTTP_HOST')) {
            return $host;
        }
       $host = $this->server('SERVER_NAME');
        if (!$host) {
            return '127.0.0.1';
        }
        $port = $this->server('SERVER_PORT');
        if (!empty($port) && $port !== '80') {
            return $host . ':' . $port;
        }
        return $host;
    }

    /**
     * 获取真实IP
     * @return string IP,
     */
   protected function createIp() {
       $realIP = filter_var($this->getIpFromServer(), FILTER_VALIDATE_IP);
       return empty($realIP) ? 'unknown' : $realIP;
    }

    protected function getIpFromServer(): string {
        $unknown = 'unknown';
        $ip = $this->server('HTTP_X_FORWARDED_FOR');
        if (!empty($ip) && strcasecmp($ip, $unknown)) {
            $arr = explode(',', $ip);
            foreach ($arr as $ip) {
                $ip = trim($ip);
                if ($ip != 'unknown') {
                    return $ip;
                }
            }
        }
        $ip = $this->server('HTTP_CLIENT_IP');
        if (!empty($ip) && strcasecmp($ip, $unknown)) {
            return $ip;
        }
        $ip = $this->server('REMOTE_ADDR');
        if (!empty($ip) && strcasecmp($ip, $unknown)) {
            return $ip;
        }
        return '';
    }

   protected function createIsMobile() {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if ($this->server('HTTP_X_WAP_PROFILE')) {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if ($via = $this->server('HTTP_VIA')) {
            // 找不到为false,否则为true
            return stristr($via, 'wap') ? true : false;
        }
        if ($agent = $this->server('HTTP_USER_AGENT')) {
            $device = UserAgent::device($agent);
            if ($device['brand']) {
                return true;
            }
        }
        // 协议法，因为有可能不准确，放到最后判断
       $accept = $this->server('HTTP_ACCEPT');
        if (empty($accept)) {
            return false;
        }
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if((strpos($accept, 'vnd.wap.wml') !== false) &&
            (strpos($accept, 'text/html') === false ||
                (strpos($accept, 'vnd.wap.wml') < strpos($accept, 'text/html')))) {
            return true;
        }
        return false;
    }

   protected function createOs() {
        return UserAgent::os($this->server('HTTP_USER_AGENT'));
    }

   protected function createBrowser() {
        return UserAgent::browser($this->server('HTTP_USER_AGENT'));
    }


}