<?php
namespace Zodream\Module\Gzo\Service;

use Zodream\Module\Gzo\Domain\GenerateModel;

class HomeController extends Controller {

    public function indexAction() {
        return $this->show('index');
    }

    public function modelAction() {
        return $this->show('model');
    }

    public function tableAction() {
        $tables = GenerateModel::schema()->getAllTable();
        return $this->ajax([
            'status' => 1,
            'tables' => $tables
        ]);
    }

    public function crudAction() {
        return $this->show('crud');
    }

    public function controllerAction() {
        return $this->show('controller');
    }
}