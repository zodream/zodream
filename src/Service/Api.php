<?php
namespace Zodream\Service;
/**
 * Created by PhpStorm.
 * User: ZoDream
 * Date: 2016/12/3
 * Time: 21:48
 */
defined('API_VERSION') || define('API_VERSION', 'v1');
class Api extends Web {
    public function setPath($path) {
        return parent::setPath(preg_replace('#^/?'.API_VERSION.'#i', '', $path));
    }
}