<?php
namespace Zodream\Service\Controller;
/**
 * 控制器基类
 *
 * @author Jason
 * @time 2015-12-19
 */
use Zodream\Domain\Html\VerifyCsrfToken;
use Zodream\Infrastructure\Event\EventManger;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Http\Response;
use Zodream\Service\Config;
use Zodream\Service\Factory;
use Zodream\Infrastructure\Loader;
use Zodream\Domain\Access\Auth;
use Zodream\Infrastructure\Traits\LoaderTrait;
use Zodream\Service\Routing\Url;

abstract class Controller extends BaseController {
	
	use LoaderTrait;

    protected $canCache;

    /**
     * AUTO CSRF
     * @var bool
     */
    protected $canCSRFValidate;
	
	function __construct($loader = null) {
		$this->loader = $loader instanceof Loader ? $loader : new Loader();
		if (is_bool($this->canCache)) {
			$this->canCache = Config::getValue('cache.auto', false);
		}
		if (is_bool($this->canCSRFValidate)) {
			$this->canCSRFValidate = Config::getValue('safe.csrf', false);
		}
	}

	public function runMethod($action, array $vars = array()) {
        if ($this->canCSRFValidate
            && Request::isPost()
            && !VerifyCsrfToken::verify()) {
            throw new \HttpRequestException('BAD POST REQUEST!');
        }
        if ($this->canCSRFValidate
            && Request::isGet()) {
            VerifyCsrfToken::create();
        }
        return parent::runMethod($action, $vars);
    }

    protected function runActionMethod($action, $vars = array()) {
        $arguments = $this->getActionArguments($action, $vars);
        if ($this->canCache && Request::isGet() &&
            (($cache = $this->runCache(get_called_class().
                    $this->action.serialize($arguments))) !== false)) {
            return $this->showContent($cache);
        }
        return call_user_func_array(array($this, $action), $arguments);
    }

    /**
     * 执行缓存
     * @param $key
     * @return bool|string
     */
    public function runCache($key = null) {
        if (DEBUG) {
            return false;
        }
        $update = Request::get('cache', false);
        if (!Auth::guest() && empty($update)) {
            return false;
        }
        if (empty($key)) {
            $key = get_called_class().$this->action;
        }
        if (!is_string($key)) {
            $key = serialize($key);
        }
        $key = 'views/'.md5($key);
        if (empty($update) && ($cache = Factory::cache()->get($key))) {
            return $cache;
        }
        $this->send('updateCache', true);
        EventManger::getInstance()->add('showView', function ($content) use ($key) {
            Factory::cache()->set($key, $content, 12 * 3600);
        });
        return false;
    }

    /**
     * 在执行之前做规则验证
     * @param string $action 方法名
     * @return boolean|Response
     */
    protected function beforeFilter($action) {
        $rules = $this->rules();
        foreach ($rules as $key => $item) {
            if ($action === $key) {
                return $this->processFilter($item);
            }
            if (is_integer($key) && is_array($item)) {
                $key = (array)array_shift($item);
                if (in_array($action, $key)) {
                    return $this->processFilter($item);
                }
            }
        }
        if (array_key_exists('*', $rules)) {
            return $this->processFilter($rules['*']);
        }
        return true;
    }

    /**
     * @param string|callable $role
     * @return bool|Response
     */
    private function processFilter($role) {
        if (is_callable($role)) {
            return call_user_func($role);
        }
        if (empty($role)) {
            return true;
        }
        if (is_string($role)) {
            $role = explode(',', $role);
        }
        foreach ((array)$role as $item) {
            if (true !== ($arg =
                    $this->processOneFilter($item))) {
                return $arg;
            }
        }
        return true;
    }

    /**
     * VALIDATE ONE FILTER
     * @param string $role
     * @return true|Response
     */
    private function processOneFilter($role) {
        if ($role === '*') {
            return true;
        }
        if ($role === '?') {
            return Auth::guest() ?: $this->redirect('/');
        }
        if ($role === '@') {
            return $this->checkUser() ?: $this->redirect([Config::getValue('auth.home'), 'ReturnUrl' => Url::to()]);
        }
        if ($role === 'p' || $role === 'post') {
            return Request::isPost() ?: $this->redirectWithMessage('/', '您不能直接访问此页面！', 4,'400');
        }
        if ($role === '!') {
            return $this->redirectWithMessage('/', '您访问的页面暂未开放！', 4, '413');
        }
        return true;
    }

    /**
     * 验证用户
     * @return bool
     */
    protected function checkUser() {
        return !Auth::guest();
    }

    /**
     * 传递数据
     *
     * @param string|array $key 要传的数组或关键字
     * @param string $value  要传的值
     * @return static
     */
    public function send($key, $value = null) {
        Factory::view()->set($key, $value);
        return $this;
    }

    /**
     * 加载视图
     *
     * @param string $name 视图的文件名
     * @param array $data 要传的数据
     * @return Response
     */
    public function show($name = null, $data = array()) {
        return $this->showContent($this->renderHtml($name, $data));
    }

    /**
     * 生成页面
     * @param string $name
     * @param array $data
     * @return string
     */
    protected function renderHtml($name = null, $data = []) {
        if (is_array($name)) {
            $data = $name;
            $name = null;
        }
        return Factory::view()->render($this->getViewFile($name), $data);
    }

    /**
     * 获取视图文件路径
     * @param string $name
     * @return string
     */
    protected function getViewFile($name = null) {
        if (is_null($name)) {
            $name = $this->action;
        }
        if (strpos($name, '/') !== 0) {
            $pattern = '.*?Service.'.APP_MODULE.'(.+)'.APP_CONTROLLER;
            $name = preg_replace('/^'.$pattern.'$/', '$1', get_called_class()).'/'.$name;
        }
        return $name;
    }

    /**
     * 直接返回文本
     * @param string $html
     * @return Response
     */
    public function showContent($html) {
        return Factory::response()->sendHtml($html);
    }

    /**
     * ajax 返回
     * @param $data
     * @param string $type
     * @return Response
     */
    public function ajax($data, $type = 'json') {
        switch (strtolower($type)) {
            case 'xml':
                return Factory::response()->sendXml($data);
            case 'jsonp':
                return Factory::response()->sendJsonp($data);
        }
        return Factory::response()->sendJson($data);
    }

    public function redirectWithMessage($url, $message, $time = 4, $status = 404) {
        return $this->redirect($url, $time);
    }

    /**
     * 重定向
     * @param string $url
     * @param int $time
     * @return Response
     */
    public function redirect($url, $time = 0) {
        return Factory::response()
            ->sendRedirect(Url::to($url), $time);
    }

    public function goHome() {
        return $this->redirect(Url::getRoot());
    }

    public function goBack() {
        return $this->redirect(Url::referrer());
    }
}