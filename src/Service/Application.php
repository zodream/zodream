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
defined('DEBUG') || define('DEBUG', false);
defined('APP_GZIP') || define('APP_GZIP', !DEBUG); // 开启gzip压缩

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
        EventManger::runEventAction('app_run');
        return Factory::router()->dispatch(Request::method(), $this->path)
            ->run()
            ->send();
    }
}