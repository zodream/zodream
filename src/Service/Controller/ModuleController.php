<?php
namespace Zodream\Service\Controller;

use Zodream\Infrastructure\Http\Request;

abstract class ModuleController extends Controller {

    /**
     * Module config setting
     */
    public function configAction() {}



    protected function getActionName($action) {
        if (Request::expectsJson()) {
            return $this->getAjaxActionName($action);
        }
        return parent::getActionName($action);
    }

    protected function getAjaxActionName($action) {
        $arg = parent::getActionName($action).'Json';
        return method_exists($this, $arg) ? $arg : parent::getActionName($action);
    }

    public function hasMethod($action) {
        return array_key_exists($action, $this->actions())
            || method_exists($this, $this->getActionName($action));
    }

    protected function getViewFile($name = null) {
        if (is_null($name)) {
            $name = $this->action;
        }
        if (strpos($name, '/') !== 0) {
            $pattern = '.*?Service.(.+)'.APP_CONTROLLER;
            $name = preg_replace('/^'.$pattern.'$/', '$1', get_called_class()).'/'.$name;
        }
        return $name;
    }
}