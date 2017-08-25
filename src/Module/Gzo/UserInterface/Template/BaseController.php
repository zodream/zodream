<?php
defined('APP_DIR') or exit();
echo '<?php';
?>

namespace Service\<?=$module?>;

use Zodream\Domain\Controller\Controller as BaseController;

abstract class Controller extends BaseController {

}