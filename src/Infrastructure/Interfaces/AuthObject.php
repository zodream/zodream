<?php
declare(strict_types = 1);

namespace Zodream\Infrastructure\Interfaces;

interface AuthObject {
	/**
	 * 获取用户信息
	 * @return UserObject
	 */
	public function user();
	
	/**
	 * 判断是否游客
	 */
	public function guest(): bool;

}