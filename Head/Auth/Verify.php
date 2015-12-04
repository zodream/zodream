<?php
namespace App\Head\Auth;

use App\Body\Config;

class Verify {
	public static function make($role) {
		if (is_object($role) && !$role()) {
			App::redirect('/');
			return false;
		} else if (is_string($role) && !empty($role)) {
			$roles = explode(',', $role);
			foreach ($roles as $value) {
				if (self::_verify($value) === false) {
					return false;
				}
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
			case '?':
				if (!call_user_func(array($auth['driver'], 'guest'))) {
					App::redirect('/');
					return false;
				}
				break;
			case '@':
				if (call_user_func(array($auth['driver'], 'guest'))) {
					App::redirect($auth['home']);
					return false;
				}
				break;
			case 'p':
				if (!App::$request->isPost()) {
					App::redirect('/', 4, '您不能直接访问此页面！', '400');
					return false;
				}
				break;
			case '!':
				App::redirect('/', 4, '您访问的页面暂未开放！', '413');
				return false;
				break;
			default:
				if (!self::judge($role)) {
					App::redirect('/', 4, '您无权操作！', '401');
					return false;
				}
				break;
		}
		return true;
	}
	
	/**
	 * 判断权限是否符合
	 * @param string $role 权限
	 */
	public static function judge($role) {
		$auth = self::_auth();
		if (call_user_func(array($auth['driver'], 'guest'))) {
			return empty($role);
		} else {
			$model = call_user_func(array($auth['driver'], 'user'));
			if (!is_null($model)) {
				return call_user_func(array($auth['role'], 'judge'), $role, $model->role()->roles);
			}
			return false;
		}
	}
}