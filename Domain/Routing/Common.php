<?php
namespace Zodream\Domain\Routing;
/**
 * 普通链接方式 即 c v
 */
use Zodream\Infrastructure\DomainObject\RouteObject;
use Zodream\Infrastructure\Request;
use Zodream\Infrastructure\ObjectExpand\ArrayExpand;
class Common implements RouteObject {
	public static function get() {
		$values = explode('/', Request::get('v' , 'index'));
		$action = array_shift($values);
		$args = array();
		for ($i = 0, $len = count($values); $i < $len; $i += 2) {
			$args[$i] = $values[$i + 1];
		}
		return array(
				str_replace('/', '\\', Request::get('c' , 'home')),
				$action,
				$args
		);
	}
	
	public static function to($file) {
		 
	}
}