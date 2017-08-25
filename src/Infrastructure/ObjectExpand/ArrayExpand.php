<?php 
namespace Zodream\Infrastructure\ObjectExpand;

/**
* array 的扩展
* 
* @author Jason
*/
use Zodream\Infrastructure\Support\Collection;

class ArrayExpand {
	
	/**
	 * 寻找第一个符合的
	 * @param array $array
	 * @param callable $callback
	 * @param null $default
	 * @return mixed
	 */
	public static function first(array $array, callable $callback, $default = null) {
		foreach ($array as $key => $value) {
			if (call_user_func($callback, $key, $value)) {
				return $value;
			}
		}
		return StringExpand::value($default);
	}

    /*** 合并前缀  把 key 作为前缀 例如 返回一个文件夹下的多个文件路径
     * array('a'=>array(
    * 'b.txt',
    * 'c.txt'
    * ))
     * @param array $args 初始
     * @param string $link 连接符
     * @param string $pre 前缀
     * @return array
     */
	public static function toFile(array $args, $link = null, $pre = null) {
		$list = array();
		if (is_array($args)) {
			foreach ($args as $key => $value) {
				if (is_int($key)) {
					if (is_array($value)) {
						$list = array_merge($list, self::toFile($value, $link, $pre));
					} elseif(is_object($value)) {
						$list[] = $value;
					} else {
						$list[] = $pre.$value;
					}
				} else {
					if (is_array($value)) {
						$list = array_merge($list, self::toFile($value, $link, $key.$link));
					} else {
						$list[] = $pre.$key.$link.$value;
					}
				}
			}
		} else {
			$list[] = $pre.$args;
		}
		return $list;
	}

    /** 把多维数组转换成字符串
     * @param array $args 数组
     * @param string $link 连接符
     * @return string
     */
	public static function toString($args, $link  = '') {
		$str = '';
		if (is_array($args)) {
			foreach ($args as $value) {
				$str .= self::toString($value, $link);
			}
		} else {
			$str .= $args.$link;
		}
		return $str;
	}

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  array  $array
     * @return array
     */
    public static function collapse($array) {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (! is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * Get all of the given array except for a specified array of items.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function except($array, $keys) {
        static::forget($array, $keys);
        return $array;
    }


    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return void
     */
    public static function forget(&$array, $keys) {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param  array  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public static function last($array, callable $callback = null, $default = null) {
        if (is_null($callback)) {
            return empty($array) ? StringExpand::value($default) : end($array);
        }

        return static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  array  $array
     * @param  int  $depth
     * @return array
     */
    public static function flatten($array, $depth = INF) {
        return array_reduce($array, function ($result, $item) use ($depth) {
            $item = $item instanceof Collection ? $item->all() : $item;

            if (! is_array($item)) {
                return array_merge($result, [$item]);
            } elseif ($depth === 1) {
                return array_merge($result, array_values($item));
            } else {
                return array_merge($result, static::flatten($item, $depth - 1));
            }
        }, []);
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param  array  $array
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    public static function pluck($array, $value, $key = null) {
        $results = [];
        list($value, $key) = static::explodePluckParameters($value, $key);

        foreach ($array as $item) {
            $itemValue = static::dataGet($item, $value);

            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = static::dataGet($item, $key);

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed   $target
     * @param  string|array  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function dataGet($target, $key, $default = null) {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (! is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (! is_array($target)) {
                    return value($default);
                }

                $result = Arr::pluck($target, $key);

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function only($array, $keys) {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Explode the "value" and "key" arguments passed to "pluck".
     *
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    protected static function explodePluckParameters($value, $key) {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }

    /**
     * Push an item onto the beginning of an array.
     *
     * @param  array  $array
     * @param  mixed  $value
     * @param  mixed  $key
     * @return array
     */
    public static function prepend($array, $value, $key = null) {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }

    /**
     * Get a value from the array, and remove it.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function pull(&$array, $key, $default = null) {
        $value = static::get($array, $key, $default);

        static::forget($array, $key);

        return $value;
    }


    /** 根据字符串获取数组值，取多维数组
     * @param string $name 要取得键
     * @param array $args
     * @param null $default
     * @param string $link
     * @return array|string
     */
	public static function getValues($name, array $args, $default = null, $link = ',') {
		$names = explode($link, $name);
        if (strpos($name, $link) === false ) {
            list($newKey, $arg, $oldKey) = self::_getValueByKeyWithDefault($name, $args, $default);
            if ($newKey == $oldKey) {
                return $arg;
            }
            return array(
                $newKey => $arg
            );
        }
		$returnValue = array();
		foreach ($names as $value) {
			list($newKey, $arg) = self::_getValueByKeyWithDefault($value, $args, $default);
            $returnValue[$newKey] = $arg;
		}
		return $returnValue;
	}

    /** 根据 "oldKey:newKey default" 获取值
     * @param string $key
     * @param array $args
     * @param null $default
     * @return array (newKey, value, oldKey)
     */
    private static function _getValueByKeyWithDefault($key,array $args, $default = null) {
        //使用方法
        list($temp, $def) = StringExpand::explode($key, ' ', 2, $default);
        $temps  = explode(':', $temp, 2);
        $oldKey = $temps[0];
        $newKey = end( $temps );
        return array(
            $newKey,
            array_key_exists($oldKey, $args) ? $args[$oldKey] : $def,
            $oldKey
        );
    }

	public static function get($array, $key, $default = null) {
		if (is_null($key)) {
			return $array;
		}

		if (isset($array[$key])) {
			return $array[$key];
		}

		foreach (explode('.', $key) as $segment) {
			if ((! is_array($array) || ! array_key_exists($segment, $array)) &&
				(! $array instanceof \ArrayAccess || ! $array->offsetExists($segment))) {
				return StringExpand::value($default);
			}

			$array = $array[$segment];
		}

		return $array;
	}

    /** 根据字符串取一个值，采用递进的方法取值
     * @param string $keys 关键字
     * @param array $values 值
     * @param null $default 默认
     * @param string $link 关键字的连接符
     * @return string|array
     */
	public static function getChild($keys, array $values, $default = null, $link = '.') {
		return self::getChildByArray(explode($link, $keys), $values, $default);
	}
	
	/**
	 * 根据关键字数组取值
	 * @param array $keys
	 * @param array $values
	 * @param null $default
	 * @return array|string
	 */
	public static function getChildByArray(array $keys, array $values, $default = null) {
		switch (count($keys)) {
			case 0:
				return $values;
			case 1:
				return array_key_exists($keys[0], $values) ? $values[$keys[0]] : $default;
			case 2:
				return isset($values[$keys[0]][$keys[1]]) ? $values[$keys[0]][$keys[1]] : $default;
			case 3:
				return isset($values[$keys[0]][$keys[1]][$keys[2]]) ? $values[$keys[0]][$keys[1]][$keys[2]] : $default;
			case 4:
				return isset($values[$keys[0]][$keys[1]][$keys[2]][$keys[3]]) ? $values[$keys[0]][$keys[1]][$keys[2]][$keys[3]] : $default;
			default:
				return isset($values[$keys[0]]) ? self::getChildByArray(array_slice($keys, 1), $values[$keys[0]], $default) : $default;
		}
	}

	/**
	 * REMOVE KEY IN ARRAY AND RETURN VALUE OR DEFAULT
	 * @param array $array
	 * @param string $key
	 * @param null $default
	 * @return mixed|null
	 */
	public static function remove(&$array, $key, $default = null) {
		if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
			$value = $array[$key];
			unset($array[$key]);

			return $value;
		}

		return $default;
	}
	
	/**
	 * 根据关键字数组取值(其中包含特殊关键字*)
	 * @param string $keys 关键字
     * @param array $values 值
     * @param null $default 默认
     * @param string $link 关键字的连接符
     * @return string|array
	 */
	public static function getChildWithStar($keys, array $values, $default = null, $link = '.') {
		$keys = explode($link, $keys, 2);
		$results = null;
		if ($keys[0] === '*') {
			$results = $values;
		} else {
			$results = array_key_exists($keys[0], $values) ? $values[$keys[0]] : $default;
		}
		if (count($keys) == 1) {
			return $results;
		}
		return self::getChildWithStar($keys[1], $results, $default, $link);
	}

    /**
	 * 扩展 array_combine 能够用于不同数目
     * @param array $keys
     * @param array $values
     * @param bool $complete
     * @return array
     */
	public static function combine(array $keys, array $values, $complete = TRUE) {
		$arr = array();
		if (!self::isAssoc($values) ) {
            for ($i = 0; $i < count($keys); $i++) {
                $arr[$keys[$i]] = isset($values[$i]) ? $values[$i] : null;
            }
            return $arr;
        }
        foreach ($keys as $key) {
        	if (isset($values[$key])) {
        		$arr[$key] = $values[$key];
        	} else if ($complete) {
        		$arr[$key] = null;
        	}
        }
		return $arr;
	}

    /** 判断是否是关联数组
     * @param array $args
     * @return bool
     */
	public static function isAssoc($args) {
		return array_keys($args) !== range(0, count($args) - 1);
	}

	/**
	 * 取关联数组的第 n 个的键值
	 * @param array $args
	 * @param int $index
	 * @return array
	 */
	public static function split(array $args, $index = 0) {
		if (count($args) <= $index) {
			return [null, null];
		}
		$i = 0;
		foreach ($args as $key => $item) {
			if ($i == $index) {
				return [$key, $item];
			}
			$i ++ ;
		}
        return [null, null];
	}

	/**
	 * 把数组的值的首字母大写
	 * @param array $arguments
	 * @return array
	 */
	public static function ucFirst(array $arguments) {
		return array_map('ucfirst', $arguments);
	}

    /**
     * GET KEY BY VALUE IN ARRAY
     * @param array $args
     * @param mixed $value
     * @return mixed
     */
	public static function getKey(array $args, $value) {
	    return array_search($value, $args);
    }

    /**
     *
     * EXAMPLE:
     *  $args = [
     *      [
     *          'a' => 12,
     *          'b' => 12323
     *      ]
     * ];
     * if $column = 'a', $indexKey = null
     * return [0 => 12],
     * else $indexKey = 'b',
     * return = [12323 => 12];
     *
     * @param array $args
     * @param string $column
     * @param string $indexKey
     * @return array
     */
    public static function getColumn(array $args, $column, $indexKey = null) {
        return array_column($args, $column, $indexKey);
    }
	
	/**
	 * 合并多个二维数组 如果键名相同后面的数组会覆盖前面的数组
	 * @param array $arr
	 * @param array ...
	 * @return array
	 */
	public static function merge2D(array $arr) {
		$args = func_get_args();
		$results = call_user_func_array('array_merge', $args);
		foreach ($results as $key => $value) {
			$temps = array();
			foreach ($args as $val) {
				$temps[] = isset($val[$key]) ? (array)$val[$key] : array();
			}
			$results[$key] = call_user_func_array('array_merge', $temps);
		}
		return $results;
	}

	/**
	 * 判断是否在二维数组中 if no return false; or return $key
	 * @param string $needle
	 * @param array $args
	 * @return bool|int|string
	 */
	public static function inArray($needle, array $args) {
		foreach ($args as $key => $value) {
			if (in_array($needle, (array)$value)) {
				return $key;
			}
		}
		return false;
	}

	/**
	 * 根据关键字排序，不是在关键字上往后移
	 *
	 *
	 * @param array $args 要排序的数组.
	 * @param array $keys 关键字数组.
	 * @return array 返回排序的数组,
	 */
	public static function sortByKey(array $args, array $keys) {
		$keyArray = $noArray = array();
		foreach ($keys as $value) {
			if (isset( $args[$value] )) {
				$keyArray[$value] = $args[$value];
			}
		}
		foreach ($args as $key => $value) {
			if (!in_array($key, $keys)) {
				$noArray[$key] = $value;
			}
		}
		return array_merge($keyArray, $noArray);
	}

	public static function keyAndValue(array $args) {
	    return [
	        key($args),
            current($args)
        ];
    }
}