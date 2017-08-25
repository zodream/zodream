<?php
namespace Zodream\Domain\Image;
/**
 * 验证码
 *
 * @author Jason
 */
use Zodream\Service\Config;

final class VerifyCode extends WaterMark {
	const CHARSET     = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';//随机因子
	private $_code;         	//验证码
	private $_length  = 4;  	    //验证码长度
	private $_fontFamily;				//指定的字体
	private $_fontSize = 30 ;	//指定字体大小
	private $_fontColor;		    //指定字体颜色

	/**
	 * 获取验证码
	 * @return string
	 */
	public function getCode() {
		$this->_createCode();
		return $this->_code;
	}

	/**
	 * 生成
	 * @param int $level 干扰等级
	 * @return resource
	 */
	public function generate($level = 0) {
		$this->_loadConfig();
		$this->_createBg();
		$this->_createText();
		$this->_createLine($level);
		return $this->image;
	}
	
	/**
	 * 载入配置
	 */
	private function _loadConfig() {
		$configs = array_merge(Config::getInstance()->get('verify', array()));
		$this->_length = isset($configs['length']) ? $configs['length'] : 4;
		$this->width = isset($configs['width']) ? $configs['width'] : 100 ;
		$this->height = isset($configs['height']) ? $configs['height'] : 30 ;
		$this->_fontSize = isset($configs['fontsize']) ? $configs['fontsize'] : 30 ;
		$this->_fontFamily = isset($configs['font']) ? $configs['font'] : 5;
	}
	
	
	/**
	 * 生成随机码
	 */
	private function _createCode() {
		$charset = self::CHARSET;
		$_len   = strlen($charset)-1;
		for ($i = 0; $i < $this->_length; $i ++) {
			$this->_code .= $charset[mt_rand(0, $_len)];
		}
	}
	
	/**
	 * 生成背景
	 */
	private function _createBg() {
		$this->create($this->width, $this->height);
		imagefilledrectangle(
				$this->image, 
				0, 
				$this->height, 
				$this->width, 
				0, 
				$this->getColorWithRGB(mt_rand(157, 255), mt_rand(157, 255), mt_rand(157, 255))
		);
	}
	
	/**
	 * 生成文字
	 */
	private function _createText() {
		$x = $this->width / $this->_length;
		for ($i = 0 ; $i < $this->_length; $i ++) {
			$this->addText(
					$this->_code[$i],
					$x*$i + mt_rand(1, 5),
					($this->height - $this->_fontSize) / 2 ,
					$this->_fontSize,
					$this->getColorWithRGB(mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156)),
					$this->_fontFamily,
					mt_rand(-30, 30)
			);
		}
	}

	/**
	 * 生成线条、雪花
	 * @param int $level
	 */
	private function _createLine($level = 1) {
		//线条
		for ($i = 0; $i < 6; $i ++) {
			imageline(
					$this->image, 
					mt_rand(0, $this->width), 
					mt_rand(0, $this->height), 
					mt_rand(0, $this->width), 
					mt_rand(0, $this->height), 
					$this->getColorWithRGB(mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156))
			);
		}
		//雪花
		for ($i = 0, $length = $level * 20; $i < $length; $i ++) {
			imagestring(
					$this->image, 
					mt_rand(1, 5), 
					mt_rand(0, $this->width), 
					mt_rand(0, $this->height), 
					'*', 
					$this->getColorWithRGB(mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255))
			);
		}
	}
}