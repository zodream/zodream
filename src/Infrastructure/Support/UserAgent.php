<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Support;

class UserAgent {
    public static function device(string $agent): array {
        $res = [
            'brand' => null
        ];
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
        if (!preg_match("/(" . implode('|', $clientKeywords) . ")/i",
            $agent, $match)) {
            return $res;
        }
        $res['brand'] = $match[1];
        return $res;
    }

    public static function browser(string $agent): array {
        $args = [
            'unknown',    //系统
            'unknown',     //系统版本
        ];
        if (stripos($agent, 'blackberry') !== false) {
            $result = explode('/', stristr($agent, 'BlackBerry'));
            if (isset($result[1])) {
                $version = explode(' ', $result[1]);
                $args[1] = $version[0];
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
            $aResult = stristr($agent, '/');
            if (preg_match('/308|425|426|474|0b1/i', $aResult)) {
                $args[1] = '1.5';
            }
            return $args;
        } // Test for versions > 1.5 and < 11 and some cases of 11
        else {
            if (stripos($agent, 'msie') !== false && stripos($agent, 'opera') === false
            ) {
                // See if the browser is the odd MSN Explorer
                if (stripos($agent, 'msnb') !== false) {
                    $aResult = explode(' ', stristr(str_replace(';', '; ', $agent), 'MSN'));
                    $args[0] = 'MSN Browser';
                    if (isset($aResult[1])) {
                        $args[1] = str_replace(array('(', ')', ';'), '', $aResult[1]);
                    }
                    return $args;
                }
                $aResult = explode(' ', stristr(str_replace(';', '; ', $agent), 'msie'));
                $args[0] = 'Internet Explorer';
                if (isset($aResult[1])) {
                    $args[1] = str_replace(array('(', ')', ';'), '', $aResult[1]);
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
                        $aResult = explode(' ', stristr($agent, 'mspie'));
                        $args[0] = 'Pocket Internet Explorer';

                        if (stripos($agent, 'mspie') !== false) {
                            if (isset($aResult[1])) {
                                $args[1] = $aResult[1];
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
                $aResult = explode('/', $resultant);
                if (isset($aResult[1])) {
                    $aversion = explode(' ', $aResult[1]);
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
            $aResult = explode('/', stristr($agent, 'OPiOS'));
            if (isset($aResult[1])) {
                $aversion = explode(' ', $aResult[1]);
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
                $aResult = explode('/', str_replace('(', ' ', $resultant));
                if (isset($aResult[1])) {
                    $aversion = explode(' ', $aResult[1]);
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
            $aResult = explode('/', stristr($agent, 'Chrome'));
            if (isset($aResult[1])) {
                $aversion = explode(' ', $aResult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'Chrome';
            return $args;
        } elseif (stripos($agent, 'CriOS') !== false) {
            $aResult = explode('/', stristr($agent, 'CriOS'));
            if (isset($aResult[1])) {
                $aversion = explode(' ', $aResult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'Chrome';
            return $args;
        }

        if (stripos($agent, 'Vivaldi') !== false) {
            $aResult = explode('/', stristr($agent, 'Vivaldi'));
            if (isset($aResult[1])) {
                $aversion = explode(' ', $aResult[1]);
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
            $aResult = explode('/', stristr($agent, 'GSA'));
            if (isset($aResult[1])) {
                $aversion = explode(' ', $aResult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'GSA';
            return $args;
        }

        if (stripos($agent, 'webtv') !== false) {
            $aResult = explode('/', stristr($agent, 'webtv'));
            if (isset($aResult[1])) {
                $aversion = explode(' ', $aResult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'WebTV';
            return $args;
        }

        if (stripos($agent, 'NetPositive') !== false) {
            $aResult = explode('/', stristr($agent, 'NetPositive'));
            if (isset($aResult[1])) {
                $aversion = explode(' ', $aResult[1]);
                $args[1] = str_replace(array('(', ')', ';'), '', $aversion[0]);
            }
            $args[0] = 'NetPositive';
            return $args;
        }

        if (stripos($agent, 'galeon') !== false) {
            $aResult = explode(' ', stristr($agent, 'galeon'));
            $aversion = explode('/', $aResult[0]);
            if (isset($aversion[1])) {
                $args[1] = $aversion[1];
            }
            $args[0] = 'Galeon';
            return $args;
        }

        if (stripos($agent, 'Konqueror') !== false) {
            $aResult = explode(' ', stristr($agent, 'Konqueror'));
            $aversion = explode('/', $aResult[0]);
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
            $aResult = explode('/', stristr($agent, 'omniweb'));
            $aversion = explode(' ', isset($aResult[1]) ? $aResult[1] : '');
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
                str_contains($agent, 'S60')
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
            $aResult = explode('/', stristr($agent, 'Iceweasel'));
            if (isset($aResult[1])) {
                $aversion = explode(' ', $aResult[1]);
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
            $aResult = explode('/', stristr($agent, 'Lynx'));
            $aversion = explode(' ', (isset($aResult[1]) ? $aResult[1] : ''));
            return [
                'Lynx',
                $aversion[0]
            ];
        }

        if (stripos($agent, 'amaya') !== false) {
            $aResult = explode('/', stristr($agent, 'Amaya'));
            if (isset($aResult[1])) {
                $aversion = explode(' ', $aResult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'Amaya';
            return $args;
        }

        if (stripos($agent, 'Safari') !== false) {
            $aResult = explode('/', stristr($agent, 'Version'));
            if (isset($aResult[1])) {
                $aversion = explode(' ', $aResult[1]);
                $args[1] = $aversion[0];
            }
            $args[0] = 'Safari';
            return $args;
        }

        if (stripos($agent, 'YaBrowser') !== false) {
            $aResult = explode('/', stristr($agent, 'YaBrowser'));
            if (isset($aResult[1])) {
                $aversion = explode(' ', $aResult[1]);
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

    public static function os(string $agent): array {
        $args = [
            'unknown',    //系统
            'unknown',     //系统版本
        ];
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
}
