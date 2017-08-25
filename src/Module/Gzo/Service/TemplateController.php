<?php
namespace Zodream\Module\Gzo\Service;

use Zodream\Infrastructure\Database\Schema\Schema;
use Zodream\Infrastructure\Disk\Directory;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\ObjectExpand\StringExpand;
use Zodream\Module\Gzo\Domain\GenerateModel;
use Zodream\Service\Factory;

class TemplateController extends Controller {

    public function indexAction($module,
                                $table,
                                $name = null,
                                $hasController = true,
                                $hasView = true,
                                $hasModel = true) {
        if (empty($name)) {
            $name = StringExpand::studly($name);
        }
        $columns = GenerateModel::schema()->table($table)->getAllColumn(true);
        if ($hasController) {
            $this->controllerAction($module, $name);
        }
        if ($hasModel) {
            $this->createModel(Factory::root()->addDirectory('Domain')
                ->addDirectory('Model')->addDirectory($module),
                $table, $module, $name, $columns, true);
        }
        if ($hasView) {
            $this->createView(Factory::root()
                ->addDirectory('UserInterface')
                ->addDirectory($module), $name, $columns);
        }
        return $this->ajaxSuccess();
    }

    public function confAction($name, $data) {
        Factory::root()->addDirectory('Service')
            ->addDirectory('config')
            ->addFile($name.'.php', $this->makeConfig($data));
        return $this->ajaxSuccess();
    }

    public function modelAction($module, $table) {
        $root = Factory::root()->addDirectory('Domain')
            ->addDirectory('Model')->addDirectory($module);
        $this->createModel($root, $table, $module);
        return $this->ajaxSuccess();
    }

    public function controllerAction($module, $name = 'Home') {
        $root = Factory::root()->addDirectory('Service')
            ->addDirectory($module);
        if (!$root->hasFile('Controller.php')) {
            $root->addFile('Controller.php', $this->baseController($module));
        }
        $this->createController($root, $name, $module);
        return $this->ajaxSuccess();
    }

    public function moduleAction($module, $table) {
        $root = Factory::root()->addDirectory('Module')
            ->addDirectory($module);
        $root->addFile('Module.php', $this->renderHtml('Module', [
            'module' => $module
        ]));
        $modelRoot = $root->addDirectory('Domain')
            ->addDirectory('Model');
        $controllerRoot = $root->addDirectory('Service');
        $viewRoot = $root->addDirectory('UserInterface');
        foreach ((array)$table as $item) {
            $columns = GenerateModel::schema()->table($item)->getAllColumn(true);
            $name = StringExpand::studly($item);
            $this->createController($controllerRoot, $name, $module, true);
            $this->createModel($modelRoot, $item, $module, $name, $columns, true);
            $this->createView($viewRoot, $name, $columns);
        }
        return $this->ajaxSuccess();
    }

    protected function createController(Directory $root, $name, $module, $is_module = false) {
        $root->addFile($name.APP_CONTROLLER.'.php', $this->makeController($name, $module, $is_module));
    }

    protected function createModel(Directory $root,
                                   $table,
                                   $module,
                                   $name = null,
                                   array $columns = [],
                                   $is_module = false) {
        if (empty($columns)) {
            $columns = GenerateModel::schema()->table($table)->getAllColumn(true);
        }
        if (empty($name)) {
            $name = StringExpand::studly($table);
        }
        $root->addFile($name.APP_MODEL.'.php', $this->makeModel($name, $table, $columns, $module, $is_module));
    }

    protected function createView(Directory $root, $name, array $columns) {
        if (!$root->hasDirectory('layout')) {
            $root->addDirectory('layout')
                ->addFile('header.php', $this->renderHtml('header'))
                ->getDirectory()->addFile('footer.php', $this->renderHtml('footer'));
        }
        $root = $root->addDirectory($name);
        $root->addFile('index.php', $this->viewIndex($name, $columns));
        $root->addFile('add.php', $this->viewEdit($name, $columns));
        $root->addFile('detail.php', $this->viewDetail($name, $columns));
    }

    /**
     * 生成基控制器
     * @param $module
     * @return string
     */
    protected function baseController($module) {
        return $this->renderHtml('BaseController', array(
            'module' => $module
        ));
    }

    /**
     * 生成控制器
     * @param string $name
     * @param string $module
     * @param bool $is_module
     * @return bool
     */
    protected function makeController($name, $module, $is_module = false) {
        return $this->renderHtml('Controller', [
            'module' => $module,
            'name' => $name,
            'is_module' => $is_module
        ]);
    }

    /**
     * 生成数据模型
     * @param string $name
     * @param string $table
     * @param array $columns
     * @param $module
     * @param bool $is_module
     * @return bool
     */
    protected function makeModel($name, $table, array $columns, $module, $is_module = false) {
        $data = GenerateModel::getFill($columns);
        $foreignKeys = (new Schema())->table($table)->getForeignKeys();
        foreach ($foreignKeys as &$item) {
            $item['table'] = StringExpand::firstReplace('zd_', '', $item['REFERENCED_TABLE_NAME']);
            $item['column'] = $item['COLUMN_NAME'];
            $item['key'] = $item['REFERENCED_COLUMN_NAME'];
        }
        return $this->renderHtml('Model', [
            'name' => $name,
            'table' => $table,
            'rules' => $data[1],
            'pk' => $data[0],
            'labels' => $data[2],
            'property' => $data[3],
            'module' => $module,
            'foreignKeys' => $foreignKeys,
            'is_module' => $is_module
        ]);
    }

    /**
     * 生成配置文件
     * @param array $configs
     * @return bool
     */
    public function makeConfig(array $configs) {
        return $this->renderHtml('config', array('data' => $configs));
    }


    /**
     * 生成主视图列表
     * @param string $name
     * @param array $columns
     * @return bool
     */
    protected function viewIndex($name, array $columns) {
        $data = [];
        foreach ($columns as $value) {
            $data[$value['Field']] = $value['Field'];
        }
        return $this->renderHtml('index', array(
            'data'   => $data,
            'name'   => $name
        ));
    }

    /**
     * 生成编辑视图
     * @param string $name
     * @param array $columns
     * @return bool
     */
    protected function viewEdit($name, array $columns) {
        $data = [];
        foreach ($columns as $value) {
            $data[] = $this->_viewForm($value);
        }
        return $this->renderHtml('add', array(
            'data'   => $data,
            'name'   => $name
        ));
    }

    /**
     * 生成单页查看视图
     * @param string $name
     * @param array $columns
     * @return bool
     */
    protected function viewDetail($name, array $columns) {
        $data = [];
        foreach ($columns as $key => $value) {
            $data[] = $value['Field'];
        }
        return $this->renderHtml('view', array(
            'data'   => $data,
            'name'   => $name
        ));
    }

    /**
     * 视图中表单的生成
     * @param $value
     * @return string
     */
    private function _viewForm($value) {
        $required = null;
        if ($value['Null'] === 'NO') {
            $required = ", 'required' => true";
        }
        switch (explode('(', $value['Type'])[0]) {
            case 'enum':
                $str = rtrim(substr($value['Type'], strpos($value['Type'], '(')), ')');
                return "select('{$value['Field']}', [{$str}])";
            case 'text':
                return "textArea('{$value['Field']}', ['label' => '{$value['Field']}'{$required}])";
            case 'int':
            case 'varchar':
            case 'char':
            default:
                return "text('{$value['Field']}', ['label' => '{$value['Field']}'{$required}])";
        }
    }

    protected function setActionArguments($name) {
        return Request::request($name);
    }

}