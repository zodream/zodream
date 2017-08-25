<?php
namespace Zodream\Service\Controller;
/**
 * 控制器基类
 *
 * @author Jason
 * @time 2015-12-19
 */
use Exception;
use Zodream\Service\Factory;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Event\EventManger;
use Zodream\Infrastructure\Http\Response;

abstract class BaseController extends Action {
	
	protected $action = 'index';
	
	protected function actions() {
		return [];
	}

	/**
	 * 此方法主要是为了继承并附加规则
	 * @return array
	 */
	protected function rules() {
		return [];
	}

    /**
     * 执行方法
     * @param string $action
     * @param array $vars
     * @return string|Response
     * @throws \Exception
     */
	public function runMethod($action, array $vars = array()) {
        Factory::timer()->record('controllerStart');
		$this->action = $action;
		if (!$this->hasMethod($action)) {
			throw new Exception('URI ERROR!');
		}
		if (true !==
            ($arg = $this->beforeFilter($action))) {
			return $arg;
		}
		if (array_key_exists($action, $this->actions())) {
			return $this->runClassMethod($action);
		}
		$this->prepare();
		EventManger::getInstance()->run('runController', $vars);
		$result = $this->runActionMethod($this->getActionName($action), $vars);
		$this->finalize();
        Factory::timer()->record('controllerEnd');
		return $result;
	}

    /**
     * 获取
     * @param $action
     * @return string
     */
	protected function getActionName($action) {
	    return $action.APP_ACTION;
    }
	
	private function runClassMethod($action) {
		$class = $this->actions()[$action];
		if (is_callable($class)) {
			return call_user_func($class);
		}
		if (!class_exists($class)) {
			throw new Exception($action. ' CANNOT RUN CLASS!');
		}
		$instance = new $class;
		if (!$instance instanceof Action) {
			throw new Exception($action. ' IS NOT ACTION!');
		}
		$instance->init();
		$instance->prepare();
		$result = $instance->run();
		$instance->finalize();
		return $result;
	}

    /**
     * RUN THIS METHOD ACTION
     * @param string $action
     * @param array $vars
     * @return mixed
     */
	protected function runActionMethod($action, $vars = array()) {
		return call_user_func_array(
		    array($this, $action),
            $this->getActionArguments($action, $vars)
        );
	}

    /**
     * GET ACTION NEED ARGUMENTS
     * @param string $action
     * @param array $vars
     * @return array
     * @throws Exception
     */
	protected function getActionArguments($action, $vars = array()) {
        $reflectionObject = new \ReflectionObject($this);
        $method = $reflectionObject->getMethod($action);
        $parameters = $method->getParameters();
        $arguments = array();
        foreach ($parameters as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $vars)) {
                $arguments[] = $vars[$name];
                continue;
            }
            $value = $this->setActionArguments($name);
            if (!is_null($value)){
                $arguments[] = $value;
                continue;
            }
            if ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
                continue;
            }
            throw new Exception($action.' ACTION`S '.$name, ' DOES NOT HAVE VALUE!');
        }
        return $arguments;
    }

    /**
     * 设置方法的注入值来源
     * @param $name
     * @return array|string  返回null时取默认值
     */
    protected function setActionArguments($name) {
        return Request::get($name);
    }

	/**
	 * 加载其他控制器的方法
	 * @param static|string $controller
	 * @param string $actionName
	 * @param array $parameters
	 */
	public function forward(
	    $controller,
        $actionName = 'index' ,
        $parameters = array()
    ) {
		if (is_string($controller)) {
			$controller = new $controller;
		}
		return $controller->runMethod($actionName, $parameters);
	}
	
	/**
	 * 判断是否存在方法
	 * @param string $action
	 * @return boolean
	 */
	public function hasMethod($action) {
		return array_key_exists($action, $this->actions())
        || method_exists($this, $action.APP_ACTION);
	}



    /**
     * 验证方法是否合法
     * @param $action
     * @return boolean|Response
     */
    abstract protected function beforeFilter($action);

}