<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Error;

use RuntimeException;
use Throwable;
use Zodream\Disk\File;
use Zodream\Disk\FileSystem;

class TemplateException extends RuntimeException {


    public function __construct(
        protected File|string|null $sourceFile,
        protected File|string|null $compiledFile,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    /**
     *
     * @return string
     */
    public function getCompiledFile(): string {
        return $this->getRelativePath($this->compiledFile);
    }

    /**
     * 执行代码的文件
     * @return string
     */
    public function getSourceFile(): string {
        return $this->getRelativePath($this->sourceFile);
    }

    private function getRelativePath(mixed $file): string {
        if (empty($file)) {
            return '';
        }
        if (defined('APP_DIR')) {
            return FileSystem::relativePath(APP_DIR, (string)$file);
        }
        return (string)$file;
    }
}