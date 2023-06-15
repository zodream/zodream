<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/12/6
 * Time: 12:28
 */

interface JsonAble {
    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson(int $options = JSON_UNESCAPED_UNICODE): string;
}