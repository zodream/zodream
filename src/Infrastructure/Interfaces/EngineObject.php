<?php
namespace Zodream\Infrastructure\Interfaces;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/16
 * Time: 15:20
 */
use Zodream\Infrastructure\Disk\File;

interface EngineObject {
    /**
     * COMPILER FILE TO CACHE FILE
     *
     * @param File $file
     * @param File $cacheFile
     * @return bool
     */
    public function compile(File $file, File $cacheFile);
}