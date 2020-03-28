<?php
namespace Zodream\Infrastructure\Http\Input;

use Zodream\Http\Uri;
use Zodream\Infrastructure\Http\UserAgent;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/3
 * Time: 9:29
 */
trait Other {


    protected function createUri(): Uri {
        $uri = new Uri();
        return $uri->setScheme($this->isSSL() ? 'https' : 'http')
            ->setHost($this->createHost())
            ->decode($this-> createUriPath());
    }

    /**
     * 获取网址
     *
     * @return string 真实显示的网址
     */
    protected function createUriPath() {
        if (isset($_SERVER['REQUEST_URI'])) {
            return $_SERVER['REQUEST_URI'];
        }
        $self = $_SERVER['PHP_SELF'];
        if (isset($_SERVER['argv'])) {
            unset($_SERVER['argv'][0]);
            return $self .'?'.implode('&', $_SERVER['argv']);
        }
        return $self .'?'. $_SERVER['QUERY_STRING'];
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
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            return [$_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']];
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
        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }
        return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
    }

    /**
     * 获取host 和port
     * @return string
     */
   protected function createHost() {
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            // 防止通过局域网代理取得ip值
            return $_SERVER['HTTP_X_FORWARDED_HOST'];
        }
        if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        }
        if (!isset($_SERVER['SERVER_NAME'])) {
            return '127.0.0.1';
        }
        $host = $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'];
        if (!empty($port) && $port != 80) {
            $host .= ':'.$port;
        }
        return $host;
    }

    /**
     * 获取真实IP
     * @return string IP,
     */
   protected function createIp() {
        $realIP  = '';
        $unknown = 'unknown';
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($arr as $ip) {
                    $ip = trim($ip);
                    if ($ip != 'unknown') {
                        $realIP = $ip;
                        break;
                    }
                }
            } else if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], $unknown)) {
                $realIP = $_SERVER['HTTP_CLIENT_IP'];
            } else if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
                $realIP = $_SERVER['REMOTE_ADDR'];
            } else {
                $realIP = $unknown;
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), $unknown)) {
                $realIP = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), $unknown)) {
                $realIP = getenv("HTTP_CLIENT_IP");
            } else if (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), $unknown)) {
                $realIP = getenv("REMOTE_ADDR");
            } else {
                $realIP = $unknown;
            }
        }
        $realIP = filter_var($realIP, FILTER_VALIDATE_IP);
        return empty($realIP) ? $unknown : $realIP;
    }

   protected function createIsMobile() {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset($_SERVER['HTTP_VIA'])) {
            // 找不到为false,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $device = UserAgent::device($_SERVER['HTTP_USER_AGENT']);
            if ($device['brand']) {
                return true;
            }
        }
        // 协议法，因为有可能不准确，放到最后判断
        if (!isset($_SERVER['HTTP_ACCEPT'])) {
            return false;
        }
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) &&
            (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false ||
                (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
        return false;
    }

   protected function createOs() {
        return UserAgent::os($_SERVER['HTTP_USER_AGENT']);
    }

   protected function createBrowser() {
        return UserAgent::browser($_SERVER['HTTP_USER_AGENT']);
    }


}