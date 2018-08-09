<?php
declare(strict_types = 1);

namespace Zodream\Service;

use Zodream\Helpers\Str;

/**
 * Class Console
 * @package Zodream\Service
 * @example php artisan gzo/module/template --module=template
 */
class Console extends Web {

    protected function formatUri(string $path): string {
        list($module, $arg) = $this->getPathAndModule();
        if ($path === '') {
            $path = $arg;
        }
        if (!empty($module)) {
            $this->instance('app.module', Str::studly($module));
        }
        $this['request']->append($this['request']->argv('options'));
        return $path;
    }

    /**
     * 获取
     * @return array
     */
    protected function getPathAndModule(): array {
        $arg = $this['request']->argv('commands.0') ?:
            $this['request']->argv('arguments.0');
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