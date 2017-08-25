<?php
defined('APP_DIR') or exit();
echo '<?php';
?>

/**
* 配置文件模板
* 
* @author Jason
*/

return [
<?php foreach ($data as $key => $item):?>
    <?php if (!is_array($item)):?>
    '<?=$key?>' => '<?=$item?>',
    <?php else:?>
    '<?=$key?>' => [
        <?php foreach ($item as $k => $it):?>
        '<?=$k?>' => '<?=$it?>',
        <?php endforeach;?>
    ],
    <?php endif;?>
<?php endforeach;?>
];