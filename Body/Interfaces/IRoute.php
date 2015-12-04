<?php
namespace App\Body\Interfaces;

interface IRoute {
	/**
	 * 获取路由
	 */
	static function get();
	
	/**
	 * 生成url
	 * @param unknown $file
	 */
	static function to($file);
}