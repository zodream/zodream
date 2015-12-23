<?php 
namespace Zodream\Infrastructure\ObjectExpand;
/**
* array 的扩展
* 
* @author Jason
*/
class ArrayExpand {
	private $before  = array();
	
	private $content = array();
	
	private $after   = array();
	
	public function arr_list($arr) {
		foreach ($arr as $key => $value) {
			if (is_numeric($key)) {
				if (is_array($value)) {
					$this->arr_list($value);
				} else {
					$this->content[] = $value;
				}
			} else {
				switch ($key) {
					case 'before':
					case 'before[]':
						if (is_array($value)) {
							$this->before = array_merge($this->before, $value);
						} else {
							$this->before[] = $value;
						}
						break;
					case 'after':
					case 'after[]':
						if (is_array($value)) {
							$this->after = array_merge($this->after, $value);
						} else {
							$this->after[] = $value;
						}
						break;
					default:
						break;
				}
			}
		}
	}
	
	/**
	 自定义排序 根据关键词 before after
	*/
	public static function sort($arg) {
		$arr = new self();
		$arr->arr_list($arg);
	
		return array_merge($arr->before, $arr->content ,$arr->after);
	}

	/***
	 合并前缀  把 key 作为前缀 例如 返回一个文件夹下的多个文件路径
	 array('a'=>arrray(
	 'b.txt',
	 'c.txt'
	 ))
	
	 **/
	public static function toFile($arr, $link = null, $pre = null) {
		$list = array();
		if (is_array($arr)) {
			foreach ($arr as $key => $value) {
				if (is_int($key)) {
					if (is_array($value)) {
						$list = array_merge($list, self::toFile($value, $link, $pre));
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
			$list[] = $pre.$arr;
		}
		return $list;
	}
	
	/**
	* 把多维数组转换成字符串
	*/
	public static function tostring($arr, $link  = '') {
		$str = '';
		if (is_array($arr)) {
			foreach ($arr as $value) {
				$str .= self::tostring($value, $link);
			}
		} else {
			$str .= $arr.$link;
		}
		return $str;
	}
	
	/****
	 * 根据字符串获取数组值，取多维数组
	 ***/
	public static function getVal($name, $values, $default = null, $link = ',') {
		$names = explode($link, $name);
		$arr   = array();
		foreach ($names as $name) {
			//使用方法 post:key default
			$temp = StringExpand::toArray($name, ' ', 2, $default);
			$def  = $temp[1];
			$temp = explode(':', $temp[0], 2);
			$name = $temp[0];
			$key  = end( $temp );
			if (isset($values[$name])) {
				$arr[$key] = $values[$name];
			} else {
				$arr[$key] = $def;
			}
		}
	
		if (count($arr) == 1) {
			foreach ($arr as $value) {
				$arr = $value;
			}
		}
	
		return $arr;
	}
	
	/**
	 * 根据字符串取一个值，采用递进的方法取值
	 */
	public static function getChild($keys, $values, $default = null, $link = '.') {
		return self::getChildByArray(explode($link, $keys), $values, $default);
	}
	
	/**
	 * 根据数组取值
	 * @param array $keys
	 * @param array $values
	 * @param unknown $default
	 * @return unknown|string|unknown
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
	 *   扩展 array_combine 能够用于不同数目
	 */
	public static function combine($keys, $values, $complete = TRUE) {
		$arr = array();
		if ( self::isAssoc($values) ) {
			foreach ($keys as $key) {
				if (isset($values[$key])) {
					$arr[$key] = $values[$key];
				} else if ($complete) {
					$arr[$key] = null;
				}
			}
		} else {
			for ($i = 0; $i < count($keys) ; $i ++) {
				$arr[$keys[$i]] = isset($values[$i]) ? $values[$i] : null;
			}
		}
	
		return $arr;
	}
	
	/**
	 *   判断是否是关联数组
	 */
	public static function isAssoc($arr) {
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
	
	/**
	 * 把数组的值的首字母大写
	 * @param array $arr
	 */
	public static function ucFirst(array $arguments) {
		return array_map('ucfirst', $arguments);
	}
	
	/**
	 * 合并多维数组 如果键名相同后面的数组会覆盖前面的数组
	 * @param array $arr
	 * @param array $arg
	 * @return array
	 */
	public static function merge(array $arr, array $arg) {
		foreach ($arg as $key => $value) {
			if (!array_key_exists($key, $arr) || !is_array($value)) {
				$arr[$key] = $value;
				continue;
			}
			$arr[$key] = self::merge((array)$arr[$key], $value);
		}
		return $arr;
	}
}