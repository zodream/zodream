<?php
namespace Zodream\Domain\Html;

use Zodream\Service\Factory;
use Zodream\Infrastructure\ObjectExpand\StringExpand;
use Zodream\Infrastructure\Http\Request;

class VerifyCsrfToken {
	/**
	 * 生成 token
	 * @return string
     */
	public static function create() {
        $token = StringExpand::random(10);
		Factory::session()->set('_token', $token);
		Factory::response()->header->setCookie('XSRF-TOKEN', $token);
		Factory::view()->set('_token', $token);
		return $token;
	}

	/*
	 * 验证
	 * @return bool
	 */
	public static function verify() {
		if (self::get() === static::getTokenFromRequest()) {
			return true;
		}
		throw new \Exception(' token 验证失败！');
	}

    protected static function getTokenFromRequest() {
        $token = Request::request('_token') ?: Request::header('X-CSRF-TOKEN');

        if (! $token && $header = Request::header('X-XSRF-TOKEN')) {
            $token = $header;
        }

        return $token;
    }

	/**
	 * 获取已经生成的 token
	 * @return string
	 */
	public static function get() {
		return Factory::session()->get('_token');
	}
}