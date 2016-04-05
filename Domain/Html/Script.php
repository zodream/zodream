<?php 
namespace Zodream\Domain\Html;
/**
 * 加载脚本
 *
 * @author Jason
 * @time 2015-12-1
 */
use Zodream\Domain\Routing\UrlGenerator;

class Script {
	public static function make($files, $dir = 'assets/') {
		$dir = rtrim($dir, '/').'/';
		foreach ($files as $file) {
			if (is_string($file) && !empty($file)) {
				if (strpos($file, '<') !== false) { // 带有 < 表示是html标签
					echo $file;
				} elseif (strpos($file, '//') === false) {
					self::makeWithRelative($file, $dir);
				} else {
					self::makeWithUrl($file);
				}
			} else if (is_object($file)) {
				$file();
			}
		}
	}
	
	private static function makeWithRelative($file, $dir) {
		$needDeal = true;
		if (substr($file, 0, 1) === '@') {
			$needDeal = false;
			$file = substr($file, 1);
		}
		$file = ltrim($file, '/');
		if (strpos($file, '.css') !== false) {
			self::makeCss(UrlGenerator::to($dir.($needDeal ? 'css/' : '').$file));
		} elseif (strpos($file, '.js') !== false) {
			self::makeJs(UrlGenerator::to($dir.($needDeal ? 'js/' : '').$file));
		} else {
			self::makeJs(UrlGenerator::to($dir.($needDeal ? 'js/' : '').$file. '.js'));
		}
	}
	
	private static function makeWithUrl($file) {
		if (strpos($file, '.css') !== false) {
			self::makeCss($file);
		} elseif (strpos($file, '.js') !== false) {
			self::makeJs($file);
		} else {
			self::makeJs($file.'.js');
		}
	}
	
	private static function makeCss($file) {
		echo '<link rel="stylesheet" type="text/css" href="'.$file.'"/>';
	}
	
	private static function makeJs($file) {
		echo '<script src="'.$file.'"></script>';
	}
	
}