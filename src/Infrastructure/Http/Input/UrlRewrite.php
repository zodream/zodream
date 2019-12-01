<?php
namespace Zodream\Infrastructure\Http\Input;

use Zodream\Helpers\Str;

trait UrlRewrite {

    /**
     * 获取网址中的虚拟路径
     * @return string
     */
    public function getVirtualUri() {
        $path = $this->request->server('PATH_INFO');
        if (!empty($path)) {
            // 在nginx 下虚拟路径无法获取
            return $path;
        }
        $script = $this->request->script().'';
        $scriptFile = basename($script);
        $path = $this->request->uri()->getPath().'';
        if (strpos($scriptFile, $path) === 0) {
            $path = rtrim($path, '/'). '/'. $scriptFile;
        } elseif (strpos($script, '.php') > 0) {
            $script = preg_replace('#/[^/]+\.php$#i', '', $script);
        }
        // 判断是否是二级文件默认入口
        if (!empty($script) && strpos($path, $script) === 0) {
            return substr($path, strlen($script));
        }
        // 判断是否是根目录其他文件入口
        if (strpos($path, $scriptFile) === 1) {
            return '/'.substr($path, strlen($scriptFile) + 1);
        }
        return $path;
    }

    /**
     * 组成重写网址
     * @param $path
     * @param array $args
     * @return array
     */
    public function enRewrite($path, array $args) {
        if (empty($path)) {
            return ['', $args];
        }
        if (empty($path) || $path == '/') {
            return ['', $args];
        }
        $ext = config('app.rewrite');
        if (!empty($ext) && strpos($path, $ext) !== false) {
            return [
                $path,
                $args
            ];
        }
        if (empty($ext) || empty($args) || count($args) > 2) {
            $path = trim($path, '/');
            return [
                !Str::endWith($path, '.php') ? $path.$ext : $path,
                $args
            ];
        }
        return $this->mergeUri(trim($path, '/'), $args, $ext);
    }

    /**
     * @param $path
     * @param array $args
     * @param string $ext
     * @return array
     */
    private function mergeUri($path, array $args, $ext = ''): array {
        $spilt = '0';
        $data = [];
        foreach ($args as $key => $arg) {
            if (!is_numeric($arg) && empty($arg)) {
                return [
                    $path . $ext,
                    $args
                ];
            }
            if (is_numeric($key) || strpos($arg, '/') !== false) {
                return [
                    $path . $ext,
                    $args
                ];
            }
            if ($spilt === '0' && is_numeric($arg) && count($args) < 2) {
                $spilt = sprintf('%s/%s', $key, $arg);
                continue;
            }
            $data[] = $key;
            $data[] = $arg;
        }
        if (!empty($data)) {
            $spilt = sprintf('%s/%s', $spilt, implode('/', $data));
        }
        return [
            sprintf('%s/%s%s', $path, $spilt, $ext),
            []
        ];
    }

    /**
     * 解析重写
     * @param $path
     * @return array
     */
    public function deRewrite($path) {
        if (empty($path)) {
            return ['', []];
        }
        $path = trim($path, '/');
        if (empty($path)) {
            return ['', []];
        }
        $ext = config('app.rewrite');
        if (!empty($ext)) {
            $path = Str::lastReplace($path, $ext);
        }
        if (empty($path)) {
            return ['', []];
        }
        list($path, $data) = $this->spiltArrayByNumber(explode('/', $path));
        return [
            implode('/', $path),
            $data
        ];
    }

    /**
     * 根据数字值分割数组
     * @param array $routes
     * @return array (routes, values)
     */
    private function spiltArrayByNumber(array $routes): array {
        $values = array();
        for ($i = 0, $len = count($routes); $i < $len; $i++) {
            if (!is_numeric($routes[$i])) {
                continue;
            }
            if ($i < $len - 1 && ($len - $i) % 2 === 1) {
                // 数字作为分割符,无意义
                $values = array_splice($routes, $i + 1);
                unset($routes[$i]);
            } else {
                $values = array_splice($routes, $i - 1);
            }
            break;
        }
        return array(
            $routes,
            $this->pairValues($values)
        );
    }

    /**
     * 将索引数组根据奇偶转关联数组
     * @param $values
     * @return array
     */
    private function pairValues($values): array {
        $args = array();
        for ($i = 0, $len = count($values); $i < $len; $i += 2) {
            if (isset($values[$i + 1])) {
                $args[$values[$i]] = $values[$i + 1];
            }
        }
        return $args;
    }
}