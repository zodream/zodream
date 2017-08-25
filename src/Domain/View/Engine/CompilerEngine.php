<?php
namespace Zodream\Domain\View\Engine;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/16
 * Time: 10:24
 */
use Zodream\Domain\View\ViewFactory;
use Zodream\Infrastructure\Disk\File;
use Zodream\Infrastructure\Interfaces\EngineObject;

abstract class CompilerEngine implements EngineObject {
    /**
     * @var ViewFactory
     */
    protected $factory;
    
    public function __construct(ViewFactory $factory) {
        $this->factory = $factory;
    }

    /**
     * COMPILER FILE TO CACHE FILE
     *
     * @param File $file
     * @param File $cacheFile
     * @return bool
     */
    public function compile(File $file, File $cacheFile) {
        return $cacheFile->write($this->compileString($file->read())) !== false;
    }

    /**
     * COMPILE STRING
     * @param string $arg
     * @return string
     */
    abstract public function compileString($arg);
}