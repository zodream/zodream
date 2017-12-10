<?php
namespace Zodream\Service;
/**
* 启动
* 
* @author Jason
* @time 2015-12-19
*/
use Zodream\Domain\Autoload;
use Zodream\Infrastructure\Event\EventManger;
use Zodream\Infrastructure\Http\Request;
use Zodream\Service\Routing\Url;

defined('VERSION') || define('VERSION', 'v3');
defined('APP_DIR') || define('APP_DIR', Request::server('DOCUMENT_ROOT'));
defined('APP_CONTROLLER') || define('APP_CONTROLLER', Config::app('controller'));
defined('APP_ACTION') || define('APP_ACTION', Config::app('action'));
defined('APP_MODEL') || define('APP_MODEL', Config::app('model'));
defined('APP_MODULE') || define('APP_MODULE', 'Default');
defined('DEBUG') || define('DEBUG', false);

class Application {

    protected $path;

    public function __construct($path = null) {
        $this->setPath($path);
    }

    public function setPath($path) {
        $this->path = $path;
        return $this;
    }

    public static function main($path = null) {
        return new static($path);
    }

    public function send() {
        date_default_timezone_set(Config::formatter('timezone'));     //这里设置了时区
        Url::setHost(Config::app('host'));
        Factory::timer()->begin();
        Autoload::getInstance()
            ->bindError();
        //Cookie::restore();
        EventManger::runEventAction('appRun');
        return Factory::router()->dispatch(Request::method(), $this->path)
            ->run()
            ->send();
    }
}