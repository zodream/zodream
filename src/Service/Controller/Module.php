<?php
namespace Zodream\Service\Controller;


/**
 * 模块基类
 *
 * @author Jason
 * @time 2015-12-19
 */
use Zodream\Database\Migrations\Migration;
use Zodream\Disk\Directory;

abstract class Module extends Action {

    /**
     * @var Directory
     */
    private $_basePath;


    /**
     * @return Directory
     */
    public function getBasePath() {
        if ($this->_basePath === null) {
            $class = new \ReflectionClass($this);
            $this->_basePath = new Directory(dirname($class->getFileName()));
        }
        return $this->_basePath;
    }

    /**
     * @return Directory.
     */
    public function getViewPath() {
        return $this->getBasePath()->childDirectory('UserInterface');
    }

    public function getControllerNamespace() {
        $class = get_class($this);
        if (($pos = strrpos($class, '\\')) !== false) {
            return substr($class, 0, $pos) . '\\Service';
        }
        return '';
    }

    /**
     * 安装
     */
    public function install() {
        $migration = $this->getMigration();
        if (!$migration instanceof Migration) {
            return;
        }
        $migration->up();
    }

    /**
     * 卸载
     */
    public function uninstall() {
        $migration = $this->getMigration();
        if (!$migration instanceof Migration) {
            return;
        }
        $migration->down();
    }

    /**
     * @return Migration
     */
    public function getMigration() {
        return;
    }

}