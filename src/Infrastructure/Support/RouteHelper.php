<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Support;

class RouteHelper {

    /**
     * 判读网址中一个路径
     * @param string $path
     * @param string $route
     * @return bool
     */
    public static function startWithRoute(string $path, string $route): bool {
        $i = strpos($path, '/');
        if ($i === false) {
            return $path === $route;
        }
        $begin = 0;
        if ($i === 0) {
            $begin = 1;
            $i = strpos($path, '/', $begin);
        }
        return substr($path, $begin, $i !== false ? $i - $begin : null) === $route;
    }

    /**
     * 根据网址获取虚拟路径
     * @param string $path
     * @param string $script
     * @return string
     */
    public static function getPathInfo(string $path, string $script): string {
        $scriptFile = basename($script);
        if (str_starts_with($scriptFile, $path)) {
            $path = rtrim($path, '/'). '/'. $scriptFile;
        } elseif (str_ends_with($script, '.php') && !str_starts_with($path, $script)) {
            // 判断 /a/hh -> /a/index.php/hh
            $script = preg_replace('#/[^/]+\.php$#i', '', $script);
        }
        // 判断是否是二级文件默认入口
        if (!empty($script) && str_starts_with($path, $script)) {
            return substr($path, strlen($script));
        }
        // 判断是否是根目录其他文件入口
        if (strpos($path, $scriptFile) === 1) {
            return '/'.substr($path, strlen($scriptFile) + 1);
        }
        return $path;
    }
}