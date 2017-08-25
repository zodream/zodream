<?php
namespace Zodream\Infrastructure\ObjectExpand;
/**
 * Created by PhpStorm.
 * User: ZoDream
 * Date: 2016/12/1
 * Time: 22:05
 */
class Format {

    public static function size($size) {
        $sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
        if ($size == 0) {
            return('n/a');
        }
        return (round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $sizes[$i]);
    }
}