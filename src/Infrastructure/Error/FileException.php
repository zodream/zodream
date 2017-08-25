<?php
namespace Zodream\Infrastructure\Error;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/3
 * Time: 9:37
 */
use Zodream\Infrastructure\Disk\FileObject;

class FileException extends Exception {

    public function __construct($message, $code = 99, Exception $previous = null) {
        if ($message instanceof FileObject) {
            $message .= ' HAS ERROR!';
        }
        parent::__construct($message, $code, $previous);
    }

    public function getName() {
        return 'FileException';
    }
}