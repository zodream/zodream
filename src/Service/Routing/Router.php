<?php 
namespace Zodream\Service\Routing;
/**
* BASE ROUTER
 * 		IT DON'T KNOW HOW TO JUDGE, BU IT'S CHILD KNOW!
* 
* @author Jason
*/
use Zodream\Service\Config;
use Zodream\Infrastructure\Interfaces\RouteObject;
use Zodream\Service\Factory;
use Zodream\Http\Uri;
use Zodream\Infrastructure\ Http\Request;

class Router {
	/**
	 * @var Route[]
	 */
	protected $routes = [];

	public function __construct() {
		$this->load();
	}
	
	protected function load() {
		$configs = Config::route([]);
		$file = array_key_exists('file', $configs) ? $configs['file'] : null;
		unset($configs['file'], $configs['driver']);
		foreach ($configs as $key => $item) {
			if (is_integer($key)) {
				call_user_func_array([$this, 'addRoute'], $item);
				continue;
			}
			$this->any($key, $item);
		}
		if (is_file($file)) {
			include $file;
		}
	}

    /**
     * 路由加载及运行方法
     * @param string|Uri $url
     * @return Route
     */
	public function getRoute($url) {
        Factory::timer()->record('route');
	    if ($url instanceof Uri) {
	        $url = $url->getPath();
        }
		$method = Request::method();
		if (isset($this->routes[$method][$url])) {
			return $this->routes[$method][$url];
		}
		if (array_key_exists($method, $this->routes)) {
			foreach ($this->routes[$method] as $item) {
			    /** @var $item Route */
				if ($item->canRun($url)) {
					return $item;
				}
			}
		}
		return new Route($method, $url, $url);
	}


	/**
	 * 手动注册路由
	 * @param $method
	 * @param $uri
	 * @param $action
	 * @return Route
	 */
	public function addRoute($method, $uri, $action) {
		$route = new Route($method, $uri, $action);
		foreach ($route->getMethods() as $item) {
			$this->routes[$item][$uri] = $route;
		}
		return $route;
	}

	public function get($uri, $action = null) {
		return $this->addRoute(['GET', 'HEAD'], $uri, $action);
	}

	/**
	 * Register a new POST route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $action
	 * @return Route
	 */
	public function post($uri, $action = null) {
		return $this->addRoute('POST', $uri, $action);
	}

	/**
	 * Register a new PUT route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $action
	 * @return Route
	 */
	public function put($uri, $action = null) {
		return $this->addRoute('PUT', $uri, $action);
	}

	/**
	 * Register a new PATCH route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $action
	 * @return Route
	 */
	public function patch($uri, $action = null) {
		return $this->addRoute('PATCH', $uri, $action);
	}

	/**
	 * Register a new DELETE route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $action
	 * @return Route
	 */
	public function delete($uri, $action = null) {
		return $this->addRoute('DELETE', $uri, $action);
	}

	/**
	 * Register a new OPTIONS route with the router.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $action
	 * @return Route
	 */
	public function options($uri, $action = null) {
		return $this->addRoute('OPTIONS', $uri, $action);
	}

	/**
	 * Register a new route responding to all verbs.
	 *
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $action
	 * @return Route
	 */
	public function any($uri, $action = null) {
		$verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'];
		return $this->addRoute($verbs, $uri, $action);
	}

	/**
	 * Register a new route with the given verbs.
	 *
	 * @param  array|string  $methods
	 * @param  string  $uri
	 * @param  \Closure|array|string|null  $action
	 * @return Route
	 */
	public function match($methods, $uri, $action = null) {
		return $this->addRoute(array_map('strtoupper', (array) $methods), $uri, $action);
	}
	
}