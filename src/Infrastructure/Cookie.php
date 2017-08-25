<?php 
namespace Zodream\Infrastructure;
/**
* I LIKE COOKIE, BUT COOKIE IS NOT SAFE, SO YOU TAKE CARE OF YOURSELF
 *		COOKIE SEND AND GET
* 
* @author Jason
*/
use Zodream\Infrastructure\Http\Request;
use Zodream\Service\Factory;

class Cookie {
	/**
	 * IF YOU FIND YOUR COOKIE IS ALWAYS SET COOKIE, BUT YOU DON'T SET,
	 * 		YOU NEED UPDATE YOU ROUTER OR USE THIS
	 */
	public static function restore() {
		foreach ($_COOKIE as $key => $value) {
			if (strpos($key, ',_') !== false) {
				$_COOKIE[str_replace(',_', '', $key)] = $value;
			}
		}
	}
	
	/**
	 * GET A COOKIE BY KEY
	 * @param string $key
	 * @param string $default
	 * @return array|string
	 */
	public static function get($key, $default = NULL) {
		 return Request::cookie($key, $default);
	}

	/**
	 * SET COOKIE, IF HEADER SENT , THIS WILL BE ERROR!
	 * @param string $name 名称
	 * @param string $value 值
	 * @param int|number $expire 有效期
	 * @param string $path 服务器路径
	 * @param string $domain 域名
	 * @param boolean $secure 是否通过安全的 HTTPS 连接来传输 cookie。
	 * @param boolean $httpOnly 是否只通过http协议 不允许js等脚本进入，防止xss
	 */
	public static function set($name, $value = '', $expire = 0, $path = null, $domain = null, $secure = FALSE, $httpOnly = FALSE) {
		Factory::response()->header->setCookie($name, $value, time() + $expire, $path, $domain, $secure, $httpOnly);
	}
	
	/**
	 * DELETE COOKIE NO YOU DELETE. 
	 * 		MAKE COOKIE EXPIRED ,
	 * 			THEN BROWSER WILL DELETE IT
	 * @param string $name 名称
	 */
	public static function delete($name) {
		Factory::response()->header->removeCookie($name);
	}
}