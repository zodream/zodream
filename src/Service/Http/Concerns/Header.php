<?php
declare(strict_types=1);
namespace Zodream\Service\Http\Concerns;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/4/3
 * Time: 9:29
 */
use Zodream\Helpers\Str;

trait Header {

    protected function createHeader(): array {
        if (function_exists('getallheaders')) {
            return $this->getUpperHeaders();
        }
        $data = [];
        foreach ($this->server() as $key => $value) {
            if (Str::startsWith($key, 'HTTP_')) {
                $data[substr($key, 5)] = $value;
            }
        }
        return $data;
    }

    /**
     * 获取大写key 处理 - 为 _
     * @return array
     */
    private function getUpperHeaders(): array {
        $data = [];
        foreach (getallheaders() as $key => $val) {
            $data[strtoupper(str_replace('-', '_', $key))] = $val;
        }
        return $data;
    }
}