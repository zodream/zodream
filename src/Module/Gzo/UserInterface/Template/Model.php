<?php
defined('APP_DIR') or exit();
echo '<?php';
?>

<?php if (isset($is_module) && $is_module):?>
namespace Module\<?=$module?>\Domain\Model;
<?php else:?>
namespace Domain\Model\<?=$module?>;
<?php endif;?>
use Domain\Model\Model;
/**
 * Class <?=$name.APP_MODEL?>

<?php foreach ($property as $key => $item):?>
 * @property <?=$item?> $<?=$key?>

<?php endforeach;?>
 */
class <?=$name.APP_MODEL?> extends Model {
	public static function tableName() {
        return '<?=$table?>';
    }

<?php if (isset($pk) && !empty($pk) && !(count($pk) == 1 && $pk[0] == 'id')):?>
	protected $primaryKey = [
<?php foreach ($pk as $item):?>
        '<?=$item?>',
<?php endforeach;?>
    ];
<?php endif;?>

	protected function rules() {
		return [
<?php foreach ($rules as $key => $item):?>
            '<?=$key?>' => '<?=$item?>',
<?php endforeach;?>
        ];
	}

	protected function labels() {
		return [
<?php foreach ($labels as $key => $item):?>
            '<?=$key?>' => '<?=$item?>',
<?php endforeach;?>
        ];
	}

<?php foreach ($foreignKeys as $item):?>
    public function get<?=ucfirst($item['table'])?>() {
        return $this->hasOne('<?=$item['table']?>', '<?=$item['key']?>', '<?=$item['column']?>');
    }

<?php endforeach;?>
}