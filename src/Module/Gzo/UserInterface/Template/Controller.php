<?php
defined('APP_DIR') or exit();
echo '<?php';
?>

<?php if (isset($is_module) && $is_module):?>
namespace Module\<?=$module?>\Service;

use Module\<?=$module?>\Domain\Model\<?=$name.APP_MODEL?>;
use Module\ModuleController;

class <?=$name.APP_CONTROLLER?> extends ModuleController {
<?php else:?>
namespace Service\<?=$module?>;

use Domain\Model\<?=$name.APP_MODEL?>;

class <?=$name.APP_CONTROLLER?> extends Controller {
<?php endif;?>
	protected $rules = array(
		'*' => '@'
	);
	
	public function index<?=APP_ACTION?>() {
		$page = <?=$name.APP_MODEL?>::findPage();
		return $this->show(array(
			'title' => '',
			'page' => $page
		));
	}

    public function add<?=APP_ACTION?>($id = null) {
        $model = $id > 0 ? <?=$name.APP_MODEL?>::findOne($id) : new <?=$name.APP_MODEL?>();
        if ($model->load() && $model->save()) {
            return $this->redirect(['<?=$name?>']);
        }
        return $this->show([
            'model' => $model
        ]);
	}

    public function delete<?=APP_ACTION?>($id) {
        <?=$name.APP_MODEL?>::findOne($id)->delete();
        return $this->redirect(['<?=$name?>']);
	}

    public function view<?=APP_ACTION?>($id) {
		$model = <?=$name.APP_MODEL?>::findOne($id);
        return $this->show([
            'model' => $model
        ]);
	}
}