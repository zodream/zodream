<?php
namespace Zodream\Service;

use Zodream\Helpers\Str;
use Zodream\Infrastructure\Http\Request;

/**
 * Class Console
 * @package Zodream\Service
 * @example php artisan gzo/module/template --module=template
 */
class Console extends Web {
    public function setPath($path) {
        list($module, $arg) = $this->getPathAndModule();
        if (is_null($path)) {
            $path = $arg;
        }
        defined('APP_MODULE') || define('APP_MODULE', Str::studly($module));
        Request::get()->set(Request::argv('options'));
        Request::request()->set(Request::argv('options'));
        return parent::setPath($path);
    }

    /**
     * 获取
     * @return array
     */
    protected function getPathAndModule() {
        $arg = Request::argv('commands.0') ?: Request::argv('arguments.0');
        if (empty($arg)) {
            return ['Home', 'index'];
        }
        $args = explode(':', $arg, 2);
        if (count($args) == 1) {
            return ['Home', $arg];
        }
        if (empty($args[0])) {
            $args[0] = 'Home';
        }
        return $args;
    }
}