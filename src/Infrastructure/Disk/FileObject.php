<?php
namespace Zodream\Infrastructure\Disk;


abstract class FileObject {
    protected $name;
    
    protected $fullName;

    /**
     * GET FILE/DIRECTORY NAME
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * GET FILE/DIRECTORY FULL NAME
     * @return string
     */
    public function getFullName() {
        return $this->fullName;
    }

    /**
     * EXIST FILE/DIRECTORY
     * @return boolean
     */
    public function exist() {
        return file_exists($this->fullName);
    }

    /**
     * IS FILE
     * @return bool
     */
    public function isFile() {
        return is_file($this->fullName);
    }

    /**
     * IS DIRECTORY
     * @return bool
     */
    public function isDirectory() {
        return is_dir($this->fullName);
    }

    /**
     * RENAME FILE
     * @param string $file
     * @param resource $context
     * @return bool
     */
    public function rename($file, $context = null) {
        return rename($this->fullName, $file, $context);
    }

    /**
     * SET FILE MODE
     * @param int $mode
     * @return bool
     */
    public function chmod($mode) {
        return chmod($this->fullName, $mode);
    }
    
    abstract public function move($file);
    
    abstract public function copy($file);
    
    abstract public function delete();
    
    public function __toString() {
        return $this->getFullName();
    }

    /**
     * WINDOWS PATH TO LINUX PATH
     * @param string $file
     * @return string
     */
    public function getSafePath($file) {
        return str_replace('\\', '/', $file);
    }

    /**
     * GET RELATIVE PATH, BUT PATH MUST BE ROOT'S CHILD
     * @param string $root
     * @return bool|string
     */
    public function getRelative($root) {
        if ($root instanceof FileObject) {
            $root = $root->getFullName();
        } else {
            $root = rtrim($this->getSafePath($root), '/');
        }
        if (strpos($this->fullName, $root) === 0) {
            return substr($this->fullName, strlen($root));
        }
        return false;
    }
}