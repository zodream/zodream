<?php 
namespace Zodream\Infrastructure;

/**
* 反射调用类
* 
* @author Jason
*/
use Zodream\Infrastructure\Base\MagicObject;
use Zodream\Service\Factory;

class Loader extends MagicObject {
	/**
	 * 添加数据类
	 * @param string $models
	 * @param string $action
	 */
	public function model($models, $action = null) {
		$this->addLoader($models, $action, 'Domain\\Model\\', config('app.model'));
	}
	
	/**
	 * 添加插件 未实例化
	 * @param string $plugin
	 */
	public function plugin($plugin) {
		$file = Factory::root()->childFile('Domain/Plugin/'. $plugin. '.php');
		if ($file->exist()) {
			include_once($file);
		}
		throw new \InvalidArgumentException(
		    __('Could not load plugin {name}!', [
		        'name' => $plugin
            ])
        );
	}

	/**
	 * 添加类
	 * @param string $library
	 * @param string $action
	 */
	public function library($library, $action = null) {
		$this->addLoader($library, $action, 'Domain\\');
	}

	/**
	 * 添加控件
	 * @param string|array $names 名字
	 * @param string $action
	 * @param string $pre 前缀
	 * @param string $after 后缀
	 * @param bool|string $up 是否大写首字母 默认 true
	 */
	private function addLoader($names, $action = null, $pre = '', $after = '', $up = true) {
		if (is_string($names)) {
			$names = explode(',', $names);
		}
		foreach ($names as $key => $value) {
			$class = $pre. ($up ? ucfirst($value) : $value). $after;
			if (class_exists($class)) {
				$instance = new $class;
				if(empty($action)) {
					call_user_func(array($instance, $action));
				}
				$this->set(is_numeric($key) ? (str_replace('\\', '_', $value).$after) : $key, $instance);
			}
			throw new \InvalidArgumentException(
			    __('Could not load {class}!', [
			        'class' => $class
                ])
            );
		}
	}
}