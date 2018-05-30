<?php
namespace Zodream\Infrastructure\Http\Input;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/3
 * Time: 9:29
 */
class Other extends BaseInput {

    public function get($name = null, $default = null) {
        if ($this->has($name)) {
            parent::get($name);
        }
        $method = 'get'.ucfirst($name);
        if (!method_exists($this, $method)) {
            return $default;
        }
        $arg = $this->$method();
        $this->set($name, $arg);
        return $arg;
    }

    /**
     * 只支持basic
     * @return array
     */
    public function getAuth() {
        if (isset($_SERVER['PHP_AUTH_USER'])) {
            return [$_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']];
        }
        return [null, null];
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
    public function getMethod() {
        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        }
        return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
    }

    /**
     * 获取host 和port
     * @return string
     */
    public function getHost() {
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
    public function getIp() {
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

    public function getIsMobile() {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset($_SERVER['HTTP_VIA'])) {
            // 找不到为false,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $clientKeywords = array ('nokia',
                'sony',
                'ericsson',
                'mot',
                'samsung',
                'htc',
                'sgh',
                'lg',
                'sharp',
                'sie-',
                'philips',
                'panasonic',
                'alcatel',
                'lenovo',
                'iphone',
                'ipod',
                'blackberry',
                'meizu',
                'android',
                'netfront',
                'symbian',
                'ucweb',
                'windowsce',
                'palm',
                'operamini',
                'operamobi',
                'openwave',
                'nexusone',
                'cldc',
                'midp',
                'wap',
                'mobile'
            );
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientKeywords) . ")/i",
                strtolower($_SERVER['HTTP_USER_AGENT']))) {
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

    public function getOs() {
        $args = [
            'unknown',    //系统
            'unknown',     //系统版本
        ];
        $agent = $_SERVER['HTTP_USER_AGENT'];
        //Chrome OS
        if (stripos($agent, 'CrOS') !== false) {
            $args[0] = 'Chrome OS';
            if (preg_match('/Chrome\/([\d\.]*)/i', $agent, $matches)) {
                $args[1] = $matches[1];
            }
            return $args;
        }
        // IOS
        if (stripos($agent, 'CPU OS') !== false ||
            stripos($agent, 'iPhone OS') !== false &&
            stripos($agent, 'OS X')) {
            $args[0] = 'iOS';
            if (preg_match('/CPU( iPhone)? OS ([\d_]*)/i', $agent, $matches)) {
                $args[1] = str_replace('_', '.', $matches[2]);
            }
            return $args;
        }
        // OSX
        if (stripos($agent, 'OS X') !== false) {
            $args[0] = 'OS X';
            if (preg_match('/OS X ([\d\._]*)/i', $agent, $matches)) {
                if (isset($matches[1])) {
                    $args[1] = str_replace('_', '.', $matches[1]);
                }
            }

            return $args;
        }

        // SymbOS
        if (stripos($agent, 'SymbOS') !== false) {
            $args[0] = 'SymbOS';
            return $args;
        }
        // Windows
        if (stripos($agent, 'Windows NT') !== false) {
            $args[0] = 'Windows';
            // Windows version
            if (preg_match('/Windows NT ([\d\.]*)/i', $agent, $matches)) {
                if (isset($matches[1])) {
                    switch (str_replace('_', '.', $matches[1])) {
                        case '6.3':
                            $args[1] = '8.1';
                            break;
                        case '6.2':
                            $args[1] = '8';
                            break;
                        case '6.1':
                            $args[1] = '7';
                            break;
                        case '6.0':
                            $args[1] = 'Vista';
                            break;
                        case '5.2':
                        case '5.1':
                            $args[1] = 'XP';
                            break;
                        case '5.01':
                        case '5.0':
                            $args[1] = '2000';
                            break;
                        case '4.0':
                            $args[1] = 'NT 4.0';
                            break;
                        default:
                            if ((float)$matches[1] >= 10.0) {
                                $args[1] = $matches[1];
                            }
                            break;
                    }
                }
            }

            return $args;
        } // Windows Me, Windows 98, Windows 95, Windows CE
        elseif (preg_match(
            '/(Windows 98; Win 9x 4\.90|Windows 98|Windows 95|Windows CE)/i',
            $agent,
            $matches
        )) {
            $args[0] = 'Windows';
            switch (strtolower($matches[0])) {
                case 'windows 98; win 9x 4.90':
                    $args[1] = 'Me';
                    break;
                case 'windows 98':
                    $args[1] = '98';
                    break;
                case 'windows 95':
                    $args[1] = '95';
                    break;
                case 'windows ce':
                    $args[1] = 'CE';
                    break;
            }
            return $args;
        }
        // Windows Phone
        if (stripos($agent, 'Windows Phone') !== false) {
            $args[0] = 'Windows Phone';
            // Windows version
            if (preg_match('/Windows Phone ([\d\.]*)/i', $agent, $matches)) {
                if (isset($matches[1])) {
                    $args[1] = (float)$matches[1];
                }
            }
            return $args;
        }

        // FreeBSD
        if (stripos($agent, 'FreeBSD') !== false) {
            $args[0] = 'FreeBSD';
            return $args;
        }

        // OpenBSD
        if (stripos($agent, 'OpenBSD') !== false) {
            $args[0] = 'OpenBSD';
            return $args;
        }

        // NetBSD
        if (stripos($agent, 'NetBSD') !== false) {
            $args[0] = 'NetBSD';
            return $args;
        }

        // OpenSolaris
        if (stripos($agent, 'OpenSolaris') !== false) {
            $args[0] = 'OpenSolaris';
            return $args;
        }

        // SunOS
        if (stripos($agent, 'SunOS') !== false) {
            $args[0] = 'SunOS';
            return $args;
        }

        // OS2
        if (stripos($agent, 'OS\/2') !== false) {
            $args[0] = 'OS2';
            return $args;
        }

        // BeOS
        if (stripos($agent, 'BeOS') !== false) {
            $args[0] = 'BeOS';
            return $args;
        }

        if (stripos($agent, 'Android') !== false) {
            if (preg_match('/Android ([\d\.]*)/i', $agent, $matches)) {
                if (isset($matches[1])) {
                    $args[1] = $matches[1];
                }
            }
            $args[0] = 'Android';
            return $args;
        }

        if (stripos($agent, 'Linux') !== false) {
            $args[0] = 'Linux';
            return $args;
        }

        if (stripos($agent, 'Nokia') !== false) {
            $args[0] = 'Nokia';
            return $args;
        }

        if (stripos($agent, 'BlackBerry') !== false) {
            $args[0] = 'BlackBerry';
            return $args;
        }
        return $args;
    }

    public function getBrowser() {
        $args = [
            'unknown',    //系统
            'unknown',     //系统版本
        ];
        $agent = $_SERVER['HTTP_USER_AGENT'];

        if (stripos($agent, 'blackberry') !== false) {
            $aresult = explode('/', stristr($agent, 'BlackBerry'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'BlackBerry';
            return $args;
        }
        if (stripos($agent, 'bot') !== false ||
            stripos($agent, 'spider') !== false ||
            stripos($agent, 'crawler') !== false
        ) {
            $args[0] = 'Robot';
            return $args;
        }

        // IE
        if (stripos($agent, 'microsoft internet explorer') !== false) {
            $args = [
                'Internet Explorer',
                '1.0'
            ];
            $aresult = stristr($agent, '/');
            if (preg_match('/308|425|426|474|0b1/i', $aresult)) {
                $args[1] = '1.5';
            }
            return $args;
        } // Test for versions > 1.5 and < 11 and some cases of 11
        else {
            if (stripos($agent, 'msie') !== false && stripos($agent, 'opera') === false
            ) {
                // See if the browser is the odd MSN Explorer
                if (stripos($agent, 'msnb') !== false) {
                    $aresult = explode(' ', stristr(str_replace(';', '; ', $agent), 'MSN'));
                    $args[0] = 'MSN Browser';
                    if (isset($aresult[1])) {
                        $args[1] = str_replace(array('(', ')', ';'), '', $aresult[1]);
                    }
                    return $args;
                }
                $aresult = explode(' ', stristr(str_replace(';', '; ', $agent), 'msie'));
                $args[0] = 'Internet Explorer';
                if (isset($aresult[1])) {
                    $args[1] = str_replace(array('(', ')', ';'), '', $aresult[1]);
                }
                // See https://msdn.microsoft.com/en-us/library/ie/hh869301%28v=vs.85%29.aspx
                // Might be 11, anyway !
                if (stripos($agent, 'trident') !== false) {
                    preg_match('/rv:(\d+\.\d+)/', $agent, $matches);
                    if (isset($matches[1])) {
                        $args[1] = $matches[1];
                    }

                    // At this poing in the method, we know the MSIE and Trident
                    // strings are present in the $userAgentString. If we're in
                    // compatibility mode, we need to determine the true version.
                    // If the MSIE version is 7.0, we can look at the Trident
                    // version to *approximate* the true IE version. If we don't
                    // find a matching pair, ( e.g. MSIE 7.0 && Trident/7.0 )
                    // we're *not* in compatibility mode and the browser really
                    // is version 7.0.
                    if (stripos($agent, 'MSIE 7.0;')) {
                        if (stripos($agent, 'Trident/7.0;')) {
                            // IE11 in compatibility mode
                            $args[1] = '11.0';
                        } elseif (stripos($agent, 'Trident/6.0;')) {
                            // IE10 in compatibility mode
                            $args[1] = '10.0';
                        } elseif (stripos($agent, 'Trident/5.0;')) {
                            // IE9 in compatibility mode
                            $args[1] = '9.0';
                        } elseif (stripos($agent, 'Trident/4.0;')) {
                            // IE8 in compatibility mode
                            $args[1] = '8.0';
                        }
                    }
                }
                return $args;
            } // Test for versions >= 11
            else {
                if (stripos($agent, 'trident') !== false) {
                    $args[0] = 'Internet Explorer';

                    preg_match('/rv:(\d+\.\d+)/', $agent, $matches);
                    if (isset($matches[1])) {
                        $args[1] = $matches[1];
                        return $args;
                    }
                } // Test for Pocket IE
                else {
                    if (stripos($agent, 'mspie') !== false ||
                        stripos(
                            $agent,
                            'pocket'
                        ) !== false
                    ) {
                        $aresult = explode(' ', stristr($agent, 'mspie'));
                        $args[0] = 'Pocket Internet Explorer';

                        if (stripos($agent, 'mspie') !== false) {
                            if (isset($aresult[1])) {
                                $args[1] = $aresult[1];
                            }
                        } else {
                            $aversion = explode('/', $agent);
                            if (isset($aversion[1])) {
                                $args[1] = $aversion[1];
                            }
                        }

                        return $args;
                    }
                }
            }
        }

        $args = [
            'unknown',
            'unknown',
        ];

        if (stripos($agent, 'opera mini') !== false) {
            $resultant = stristr($agent, 'opera mini');
            if (preg_match('/\//', $resultant)) {
                $aresult = explode('/', $resultant);
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $args[1] = $aversion[0];
                }
            } else {
                $aversion = explode(' ', stristr($resultant, 'opera mini'));
                if (isset($aversion[1])) {
                    $args[1] = $aversion[1];
                }
            }
            $args[0] = 'Opera Mini';
            return $args;
        } elseif (stripos($agent, 'OPiOS') !== false) {
            $aresult = explode('/', stristr($agent, 'OPiOS'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'Opera Mini';
            return $args;
        } elseif (stripos($agent, 'opera') !== false) {
            $resultant = stristr($agent, 'opera');
            if (preg_match('/Version\/(1[0-2].*)$/', $resultant, $matches)) {
                if (isset($matches[1])) {
                    $args[1] = $matches[1];
                }
            } elseif (preg_match('/\//', $resultant)) {
                $aresult = explode('/', str_replace('(', ' ', $resultant));
                if (isset($aresult[1])) {
                    $aversion = explode(' ', $aresult[1]);
                    $args[1] = $aversion[0];
                }
            } else {
                $aversion = explode(' ', stristr($resultant, 'opera'));
                $args[1] = isset($aversion[1]) ? $aversion[1] : '';
            }
            $args[0] = 'Opera';
            return $args;
        } elseif (stripos($agent, ' OPR/') !== false) {
            $args[0] = 'Opera';
            if (preg_match('/OPR\/([\d\.]*)/', $agent, $matches)) {
                if (isset($matches[1])) {
                    $args[1] = $matches[1];
                }
            }
            return $args;
        }

        if (stripos($agent, 'Chrome') !== false) {
            $aresult = explode('/', stristr($agent, 'Chrome'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'Chrome';
            return $args;
        } elseif (stripos($agent, 'CriOS') !== false) {
            $aresult = explode('/', stristr($agent, 'CriOS'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'Chrome';
            return $args;
        }

        if (stripos($agent, 'Vivaldi') !== false) {
            $aresult = explode('/', stristr($agent, 'Vivaldi'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'Vivaldi';
            return $args;
        }

        if (stripos($agent, 'Edge') !== false) {
            $version = explode('Edge/', $agent);
            if (isset($version[1])) {
                $args[1] = (float)$version[1];
            }
            $args[0] = 'Edge';
            return $args;
        }

        if (stripos($agent, 'GSA') !== false) {
            $aresult = explode('/', stristr($agent, 'GSA'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'GSA';
            return $args;
        }

        if (stripos($agent, 'webtv') !== false) {
            $aresult = explode('/', stristr($agent, 'webtv'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'WebTV';
            return $args;
        }

        if (stripos($agent, 'NetPositive') !== false) {
            $aresult = explode('/', stristr($agent, 'NetPositive'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $args[1] = str_replace(array('(', ')', ';'), '', $aversion[0]);
            }
            $args[0] = 'NetPositive';
            return $args;
        }

        if (stripos($agent, 'galeon') !== false) {
            $aresult = explode(' ', stristr($agent, 'galeon'));
            $aversion = explode('/', $aresult[0]);
            if (isset($aversion[1])) {
                $args[1] = $aversion[1];
            }
            $args[0] = 'Galeon';
            return $args;
        }

        if (stripos($agent, 'Konqueror') !== false) {
            $aresult = explode(' ', stristr($agent, 'Konqueror'));
            $aversion = explode('/', $aresult[0]);
            if (isset($aversion[1])) {
                $args[1] = $aversion[1];
            }
            $args[0] = 'Konqueror';
            return $args;
        }

        if (stripos($agent, 'icab') !== false) {
            $aversion = explode(' ', stristr(str_replace('/', ' ', $agent), 'icab'));
            if (isset($aversion[1])) {
                $args[1] = $aversion[1];
            }
            $args[0] = 'iCab';
            return $args;
        }

        if (stripos($agent, 'omniweb') !== false) {
            $aresult = explode('/', stristr($agent, 'omniweb'));
            $aversion = explode(' ', isset($aresult[1]) ? $aresult[1] : '');
            return [
                'OmniWeb',
                $aversion[0]
            ];
        }

        if (stripos($agent, 'Phoenix') !== false) {
            $aversion = explode('/', stristr($agent, 'Phoenix'));
            if (isset($aversion[1])) {
                $args[1] = $aversion[1];
            }
            $args[0] = 'Phoenix';
            return $args;
        }

        if (stripos($agent, 'Firebird') !== false) {
            $aversion = explode('/', stristr($agent, 'Firebird'));
            if (isset($aversion[1])) {
                $args[1] = $aversion[1];
            }
            $args[0] = 'Firebird';
            return $args;
        }

        if (stripos($agent, 'Firefox') !== false &&
            preg_match('/Navigator\/([^ ]*)/i', $agent, $matches)
        ) {
            if (isset($matches[1])) {
                $args[1] = $matches[1];
            }
            $args[0] = 'Netscape Navigator';
            return $args;
        } elseif (stripos($agent, 'Firefox') === false &&
            preg_match('/Netscape6?\/([^ ]*)/i', $agent, $matches)
        ) {
            if (isset($matches[1])) {
                $args[1] = $matches[1];
            }
            $args[0] = 'Netscape Navigator';
            return $args;
        }

        if (stripos($agent, 'Mozilla') !== false &&
            preg_match('/Shiretoko\/([^ ]*)/i', $agent, $matches)
        ) {
            if (isset($matches[1])) {
                $args[1] = $matches[1];
            }
            $args[0] = 'Shiretoko';
            return $args;
        }

        if (stripos($agent, 'Mozilla') !== false &&
            preg_match('/IceCat\/([^ ]*)/i', $agent, $matches)
        ) {
            if (isset($matches[1])) {
                $args[1] = $matches[1];
            }
            $args[0] = 'IceCat';
            return $args;
        }

        if (preg_match("/Nokia([^\/]+)\/([^ SP]+)/i", $agent, $matches)) {
            $args[1] = $matches[2];
            if (stripos($agent, 'Series60') !== false ||
                strpos($agent, 'S60') !== false
            ) {
                $args[0] = 'Nokia S60 OSS Browser';
            } else {
                $args[0] = 'Nokia Browser';
            }

            return $args;
        }

        if (stripos($agent, 'safari') === false) {
            if (preg_match("/Firefox[\/ \(]([^ ;\)]+)/i", $agent, $matches)) {
                if (isset($matches[1])) {
                    $args[1] = $matches[1];
                }
                $args[0] = 'Firefox';
                return $args;
            } elseif (preg_match('/Firefox$/i', $agent, $matches)) {
                return [
                    'Firefox',
                    ''
                ];
            }
        }

        if (stripos($agent, 'safari') === false) {
            if (preg_match("/SeaMonkey[\/ \(]([^ ;\)]+)/i", $agent, $matches)) {
                if (isset($matches[1])) {
                    $args[1] = $matches[1];
                }
                $args[0] = 'SeaMonkey';
                return $args;
            } elseif (preg_match('/SeaMonkey$/i', $agent, $matches)) {
                return [
                    'SeaMonkey',
                    ''
                ];
            }
        }

        if (stripos($agent, 'Iceweasel') !== false) {
            $aresult = explode('/', stristr($agent, 'Iceweasel'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'Iceweasel';
            return $args;
        }

        if (stripos($agent, 'mozilla') !== false &&
            preg_match('/rv:[0-9].[0-9][a-b]?/i', $agent) &&
            stripos($agent, 'netscape') === false
        ) {
            $aversion = explode(' ', stristr($agent, 'rv:'));
            preg_match('/rv:[0-9].[0-9][a-b]?/i', $agent, $aversion);

            return [
                'Mozilla',
                str_replace('rv:', '', $aversion[0])
            ];
        } elseif (stripos($agent, 'mozilla') !== false &&
            preg_match('/rv:[0-9]\.[0-9]/i', $agent) &&
            stripos($agent, 'netscape') === false
        ) {
            $aversion = explode('', stristr($agent, 'rv:'));
            return [
                'Mozilla',
                str_replace('rv:', '', $aversion[0])
            ];
        } elseif (stripos($agent, 'mozilla') !== false &&
            preg_match('/mozilla\/([^ ]*)/i', $agent, $matches) &&
            stripos($agent, 'netscape') === false
        ) {
            if (isset($matches[1])) {
                $args[1] = $matches[1];
            }
            $args[0] = 'Mozilla';
            return $args;
        }

        if (stripos($agent, 'lynx') !== false) {
            $aresult = explode('/', stristr($agent, 'Lynx'));
            $aversion = explode(' ', (isset($aresult[1]) ? $aresult[1] : ''));
            return [
                'Lynx',
                $aversion[0]
            ];
        }

        if (stripos($agent, 'amaya') !== false) {
            $aresult = explode('/', stristr($agent, 'Amaya'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'Amaya';
            return $args;
        }

        if (stripos($agent, 'Safari') !== false) {
            $aresult = explode('/', stristr($agent, 'Version'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'Safari';
            return $args;
        }

        if (stripos($agent, 'YaBrowser') !== false) {
            $aresult = explode('/', stristr($agent, 'YaBrowser'));
            if (isset($aresult[1])) {
                $aversion = explode(' ', $aresult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'Yandex';
            return $args;
        }

        if (stripos($agent, 'Android') !== false) {
            if (preg_match('/Version\/([\d\.]*)/i', $agent, $matches)) {
                if (isset($matches[1])) {
                    $args[1] = $matches[1];
                }
            }
            $args[0] = 'Navigator';
            return $args;
        }
        
        return $args;
    }


}