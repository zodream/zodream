<?php
namespace Zodream\Controller;
/*
 * 模板
 *
 * @author Jason
 * @time 2015-12-1
 */
use Zodream;
use Zodream\Model\TempletModel;
use Zodream\Lib\Helper\HUrl;

class TempletController extends Controller {
	protected $rules = array (
		'add'    => '@',
		'edit'   => '@',
		'delete' => '@'
	);
	
	function indexAction($limit) {
		$model = new TempletModel();
		$this->show('templet.index', array(
			'data' => $model->findLimit($limit)
		));
	}
	
	function addAction() {
		$model = new TempletModel();
		if (App::$request->isPost()) {
			$model->fill(App::$request->post());
		}
		$this->show('templet.add', array());
	}
	
	function editAction($id) {
		$model = new TempletModel();
		if (App::$request->isPost()) {
			$model->updateById(App::$request->post(), $id);
		}
		
		$this->show('templet.add', array(
			'data' => $model->findById($id)
		));
	}
	
	function deleteAction($id) {
		$model = new TempletModel();
		$model->deleteById($id);
		App::redirect(HUrl::referer());
	}
	
	function ViewAction($id) {
		$model = new TempletModel();
		$this->show('templet.view', array(
			'data' => $model->findById($id)
		));
	}
}