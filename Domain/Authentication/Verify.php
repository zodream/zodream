<?php
namespace Zodream\Domain\Authentication;


use Zodream\Domain\Routing\Url;
use Zodream\Infrastructure\Config;
use Zodream\Domain\Response\Redirect;
use Zodream\Infrastructure\Request;

class Verify {
	public static function make($role) {
		if (is_object($role) && !$role()) {
			Redirect::to('/');
			return false;
		}
		if (empty($role)) {
			return true;
		}

		if (is_string($role)) {
			$role = explode(',', $role);
		}
		foreach ((array)$role as $value) {
			if (self::_verify($value) === false) {
				return false;
			}
		}
		return true;
	}
	
	private static function _auth() {
		static $auth = null;
		if (empty($auth)) {
			$auth = Config::getInstance()->get('auth');
		}
		return $auth;
	}
	
	private static function _verify($role) {
		$auth = self::_auth();
		switch ($role) {
            case '*':
                return TRUE;
			case '?':
				if (!call_user_func(array($auth['driver'], 'guest'))) {
					Redirect::to('/');
					return false;
				}
				break;
			case '@':
				if (call_user_func(array($auth['driver'], 'guest'))) {
					Redirect::to([$auth['home'], 'ReturnUrl' => Url::to()]);
					return false;
				}
				break;
			case 'p':
				if (!Request::isPost()) {
					Redirect::to('/', 4, '您不能直接访问此页面！', '400');
					return false;
				}
				break;
			case '!':
				Redirect::to('/', 4, '您访问的页面暂未开放！', '413');
				return false;
				break;
			default:
				if (!self::judge($role)) {
					Redirect::to('/', 4, '您无权操作！', '401');
					return false;
				}
				break;
		}
		return true;
	}

	/**
	 * 判断权限是否符合
	 * @param string $role 权限
	 * @return bool
	 */
	public static function judge($role) {
		$auth = self::_auth();
		if (call_user_func(array($auth['driver'], 'guest'))) {
			return empty($role);
		}
		return call_user_func(array($auth['role'], 'judge'), $role);
	}
}
