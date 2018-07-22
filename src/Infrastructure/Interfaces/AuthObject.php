<?php
namespace Zodream\Infrastructure\Interfaces;

interface AuthObject {
	/**
	 * 获取用户信息
	 * @return UserObject
	 */
	static function user();
	
	/**
	 * 判断是否游客
	 */
	static function guest();

}