<?php
declare(strict_types = 1);

namespace Zodream\Service;

use Zodream\Infrastructure\Http\Request;
use Zodream\Service\Routing\Url;

class Web extends Application {

    protected function formatUri(string $path): string {
        if (is_null($path)) {
            $path = URL::getVirtualUri();
        }
        return $this->getRealPath($path);
    }

    protected function getRealPath(string $path): string {
        list($routes, $args) = $this->spiltArrayByNumber(explode('/', trim($path, '/')));
        $this->request->append($args);
        return implode('/', $routes);
    }

    /**
     * 根据数字值分割数组
     * @param array $routes
     * @return array (routes, values)
     */
    protected function spiltArrayByNumber(array $routes): array {
        $values = array();
        for ($i = 0, $len = count($routes); $i < $len; $i++) {
            if (!is_numeric($routes[$i])) {
                continue;
            }
            if (($len - $i) % 2 == 0) {
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
    protected function pairValues($values): array {
        $args = array();
        for ($i = 0, $len = count($values); $i < $len; $i += 2) {
            if (isset($values[$i + 1])) {
                $args[$values[$i]] = $values[$i + 1];
            }
        }
        return $args;
    }
}