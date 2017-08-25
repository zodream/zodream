<?php 
namespace Zodream\Infrastructure\ObjectExpand;

/**
* string 的扩展
* 
* @author Jason
*/

class StringExpand {
    /**
     * 获取值
     * @param string $value
     * @return mixed
     */
	public static function value($value) {
		return is_callable($value) ? call_user_func($value) : $value;
	}
    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function contains($haystack, $needles) {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

	/**
	 * 拓展str_repeat 重复字符并用字符连接
	 * @param string $str
	 * @param integer $count
	 * @param string $line
	 * @return string
	 */
	public static function repeat($str, $count, $line = ',') {
		return substr(str_repeat($str.$line, $count), 0, - strlen($line));
	}

    /**
     * Determine if a given string matches a given pattern.
     *
     * @param  string  $pattern
     * @param  string  $value
     * @return bool
     */
    public static function is($pattern, $value) {
        if ($pattern == $value) {
            return true;
        }
        $pattern = preg_quote($pattern, '#');
        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern);
        return (bool) preg_match('#^'.$pattern.'\z#u', $value);
    }

	/**
	 * 生成更加真实的随机字符串
	 *
	 * @param  int  $length
	 * @return string
	 */
	public static function random($length = 16) {
		if (function_exists('str_random')) {
			return str_random($length);
		}
		$string = '';
		while (($len = strlen($string)) < $length) {
			$size = $length - $len;
			$bytes = static::randomBytes($size);
			$string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
		}
		return $string;
	}

	public static function randomInt($min, $max) {
	    if (function_exists('random_int')) {
	        return random_int($min, $max);
        }
        if (!function_exists('mcrypt_create_iv')) {
            trigger_error(
                'mcrypt must be loaded for random_int to work',
                E_USER_WARNING
            );
            return null;
        }
        if (!is_int($min) || !is_int($max)) {
            trigger_error('$min and $max must be integer values', E_USER_NOTICE);
            $min = (int)$min;
            $max = (int)$max;
        }
        if ($min > $max) {
            trigger_error('$max can\'t be lesser than $min', E_USER_WARNING);
            return null;
        }
        $range = $counter = $max - $min;
        $bits = 1;
        while ($counter >>= 1) {
            ++$bits;
        }
        $bytes = (int)max(ceil($bits / 8), 1);
        $bitmask = pow(2, $bits) - 1;
        if ($bitmask >= PHP_INT_MAX) {
            $bitmask = PHP_INT_MAX;
        }
        do {
            $result = hexdec(
                    bin2hex(
                        mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM)
                    )
                ) & $bitmask;
        } while ($result > $range);

        return $result + $min;
    }

	/**
	 * 生成随机数字字符串
	 * @param $length
	 * @return string
	 */
	public static function randomNumber($length = 6) {
		return sprintf('%0'.$length.'d', mt_rand(0, pow(10, $length) - 1));
	}

    /**
     * 生成更加真实的随机字节
     *
     * @param integer $length
     * @return string
     * @throws \ErrorException
     */
	public static function randomBytes($length = 16) {
		if (PHP_MAJOR_VERSION >= 7 || defined('RANDOM_COMPAT_READ_BUFFER')) {
			return random_bytes($length);
		} elseif (function_exists('openssl_random_pseudo_bytes')) {
			$bytes = openssl_random_pseudo_bytes($length, $strong);
			if ($bytes === false || $strong === false) {
				throw new \InvalidArgumentException('Unable to generate random string.');
			}
			return $bytes;
		}
		throw new \ErrorException('OpenSSL extension or paragonie/random_compat is required for PHP 5 users.');
	}

	/**
	 * 替换url中的参数
	 *
	 * @param string $url
	 * @param string|array $key
	 * @param null|string $value
	 * @return string 合并后的值
	 */
	public static function urlBindValue($url, $key , $value = null) {
		$arr = explode('?', $url, 2);
		$arr = str_replace('&amp;', '&', $arr);      //解决 & 被转义
		$data = array();
		if (count($arr) > 1) {
			parse_str($arr[1], $data);
		}
		if (!is_null($value)) {
			$data[$key] = $value;
            return $arr[0].'?'.http_build_query($data);
		}
        if (is_array($key)) {
            foreach ($key as $k => $val) {
                if (!is_integer($k)) {
                    $data[$k] = $val;
                    continue;
                }
                $temps = self::explode($val, '=', 2);
                $data[$temps[0]] = $temps[1];
            }
        } else if (is_string($key)) {
            $keys = array();
            parse_str($key, $keys);
            $data = array_merge($data, $keys);
        }
		return $arr[0].'?'.http_build_query($data);
	}
	/**
	 * 生成简单的随机字符串
	 * @param  int  $length
	 * @return string
	 */
	public static function quickRandom($length = 16) {
		$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
	}

    /**
     * @param int $length 总长度
     * @param int $arg 数字转字符串
     * @param string $pool 随机字符串参考
     * @return string
     */
    public static function randomByNumber(
        $length = 6,
        $arg = 0,
        $pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ) {
        $arg = intval($arg);
        $str = '';
        $len = 0;
        $max = strlen($pool);
        while ($arg > 0) {
            $index = $arg % $max;
            $str = $pool[$index].$str;
            $len ++;
            $arg = floor($arg / $max);
        }
        return substr($str.str_shuffle(str_repeat($pool, $length - $len)), 0, $length);
    }

	/**
	 * 字节长度
	 * @param string $string
	 * @return integer
	 */
	public static function byteLength($string) {
		return mb_strlen($string, '8bit');
	}

	/**
	 * 截取字符串为数组，补充explode函数
	 * @param $str
	 * @param string $link
	 * @param int $num
	 * @param array|string $default 不存在时使用
	 * @return array
	 */
	public static function explode($str, $link = ' ', $num = 1, $default = null) {
		$args = explode($link, $str, $num);
		if (count($args) >= $num) {
			return $args;
		}
		if (!is_array($default)) {
			return array_pad($args, $num, $default);
		}
		for ($i = $num - 1; $i >= 0 ; $i --) {
			if (!array_key_exists($i, $args)) {
				return $args;
			}
			$args[$i] = $default[$i];
		}
		return $args;
	}

	/**
	 * EXPLODE STRING BY ARRAY
	 * @param array $delimiters
	 * @param $string
	 * @return array
	 */
	public static function multiExplode(array $delimiters, $string) {
		$ready = str_replace($delimiters, $delimiters[0], $string);
		return explode($delimiters[0], $ready);
	}

	/**
	 * 判断字符串是否以$needles开头
	 * @param string $haystack
	 * @param string|array $needles 要寻找的字符串
	 * @return bool
	 */
	public static function startsWith($haystack, $needles) {
		foreach ((array) $needles as $needle) {
			if ($needle != '' && strpos($haystack, $needle) === 0) {
				return true;
			}
		}
		return false;
	}

    /**
     * 是否以。。。结尾
     * @param string $search
     * @param string $arg
     * @return bool
     */
	public static function endWith($arg, $search) {
	    return strrchr($arg, $search) == $search;
    }

	/**
	 * 首字符替换
	 * @param string $search
	 * @param string $arg
	 * @param string $replace
	 * @return string
	 */
	public static function firstReplace($arg, $search, $replace = null) {
		return preg_replace('/^'.$search.'/', $replace, $arg, 1);
	}

	public static function lastReplace($arg, $search, $replace = null) {
		return preg_replace('/'.$search.'$/', $replace, $arg, 1);
	}

    /**
     * Convert a value to studly caps case.
     *
     * @param  string  $value
     * @return string
     */
    public static function studly($value) {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', $value);
    }

	/**
	 * UTF8字符串的长度
	 * @param string $str
	 * @return int
	 */
	public static function absLength($str) {
		if (empty($str)) {
			return 0;
		}
		if (function_exists('mb_strlen')) {
			return mb_strlen($str,'utf-8');
		}
        preg_match_all("/./u", $str, $ar);
        return count($ar[0]);
	}

	/**
	 * UTF8字符串截取
	 * @param string $str
	 * @param int $start
	 * @param int $length
	 * @return bool|string
	 */
	public static function subString($str, $start = 0, $length = 0) {
		if (empty($str)) {
			return false;
		}
		if (function_exists('mb_substr')) {
			if(func_num_args() >= 3) {
				$end = func_get_arg(2);
				return mb_substr($str,$start,$end,'utf-8');
			}
            mb_internal_encoding("UTF-8");
            return mb_substr($str,$start);
		}
        $null = "";
        preg_match_all("/./u", $str, $ar);
        if (func_num_args() >= 3) {
            $end = func_get_arg(2);
            return join($null, array_slice($ar[0], $start, $end));
        }
        return join($null, array_slice($ar[0], $start));
	}
	
	/*
	 * 中文截取，支持gb2312,gbk,utf-8,big5
	 *
	 * @param string $str 要截取的字串
	 * @param int $start 截取起始位置
	 * @param int $length 截取长度
	 * @param string $charset utf-8|gb2312|gbk|big5 编码
	 * @param $suffix 是否加尾缀
	 */
	
	public static function subStr($str, $start, $length, $charset = 'utf-8', $suffix = true) {
		if (function_exists("mb_substr")) {
			if (mb_strlen($str, $charset) <= $length) return $str;
			$slice = mb_substr($str, $start, $length, $charset);
		} else {
			$re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
			$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
			$re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
			$re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
			preg_match_all($re[$charset], $str, $match);
			if (count($match[0]) <= $length) return $str;
			$slice = join("", array_slice($match[0], $start, $length));
		}
		if ($suffix) return $slice."…";
		return $slice;
	}
	
}