<?php
namespace Zodream\Service;
/**
* 启动
* 
* @author Jason
* @time 2015-12-19
*/
use Zodream\Domain\Autoload;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Http\Response;
use Zodream\Service\Events\RequestHandled;
use Zodream\Service\Routing\Url;
use Exception;
use Throwable;
use Zodream\Infrastructure\Error\FatalThrowableError;

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

    /**
     * @return bool
     * @throws Exception
     */
    public function send() {
        return $this->handle()->send();
    }

    /**
     * 运行程序并捕捉错误信息
     * @return Response
     * @throws Exception
     */
    public function handle() {
        try {
            $response = $this->sendRequestThroughRouter();
        } catch (Exception $e) {
            $this->reportException($e);

            $response = $this->renderException($e);
        } catch (Throwable $e) {
            $this->reportException($e = new FatalThrowableError($e));

            $response = $this->renderException($e);
        }

        Factory::event(new RequestHandled($response));
        return $response;
    }

    /**
     * 执行路由并获取响应
     * @return Response
     * @throws \Exception
     */
    protected function sendRequestThroughRouter() {
        $this->bootstrap();
        return Factory::router()
            ->dispatch(Request::method(), $this->path)
            ->run();
    }

    /**
     * 配置程序全局信息
     * @throws Exception
     */
    public function bootstrap() {
        date_default_timezone_set(Config::formatter('timezone'));     //这里设置了时区
        Url::setHost(Config::app('host'));
        Factory::timer()->begin();
        Autoload::getInstance()
            ->registerAlias()
            ->bindError();
        //Cookie::restore();
        $configs = Config::event([]);
        $this->registerEvents($configs);
    }


    /**
     * Report the exception to the exception handler.
     *
     * @param  Exception $e
     * @return void
     * @throws Exception
     */
    protected function reportException(Exception $e) {
        Factory::handler()->report($e);
    }

    /**
     * Render the exception to a response.
     * @param Exception $e
     * @return Response
     * @throws Exception
     */
    protected function renderException(Exception $e) {
        return Factory::handler()->render($e);
    }

    /**
     * @param $configs
     * @throws Exception
     */
    public function registerEvents($configs) {
        if (!isset($configs['canAble']) || !$configs['canAble']) {
            Factory::event()->setCanAble(false);
            return;
        }
        foreach ($configs as $key => $item) {
            if ($key == 'canAble' || empty($item)) {
                continue;
            }
            foreach ((array)$item as $value) {
                Factory::event()->add($key, $value);
            }
        }
    }
}