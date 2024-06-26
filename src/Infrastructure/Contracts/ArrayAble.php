<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/12/6
 * Time: 12:27
 */
interface ArrayAble {
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array;
}