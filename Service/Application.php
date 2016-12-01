<?php
namespace Zodream\Service;
/**
* 启动
* 
* @author Jason
* @time 2015-12-19
*/
use Zodream\Domain\Autoload;
use Zodream\Infrastructure\Config;
use Zodream\Infrastructure\DomainObject\ResponseObject;
use Zodream\Infrastructure\Factory;
use Zodream\Infrastructure\Event\EventManger;
use Zodream\Infrastructure\Response;
use Zodream\Infrastructure\Url\DefaultUri;

defined('VERSION') || define('VERSION', 2.3);
defined('APP_DIR') || define('APP_DIR', dirname(dirname(__FILE__)).'/');
defined('APP_CONTROLLER') || define('APP_CONTROLLER', Config::getInstance()->get('app.controller'));
defined('APP_ACTION') || define('APP_ACTION', Config::getInstance()->get('app.action'));
defined('APP_MODEL') || define('APP_MODEL', Config::getInstance()->get('app.model'));
defined('DEBUG') || define('DEBUG', false);

class Application {
	/**
	 * APP RUN IN THIS
	 * @param array $configs
	 * @return Response
	 */
	public static function main($configs = []) {
	    Factory::timer()->begin();
		Config::getInstance($configs);
		Autoload::getInstance()
			->setError()
			->shutDown();
		//Cookie::restore();
		EventManger::getInstance()->run('appRun');
		$route = Factory::router()
            ->run(new DefaultUri());
		return $route->run();
	}
}