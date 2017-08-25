<?php
namespace Zodream\Module\OAuth\Service;
use Zodream\Infrastructure\Http\Component\Uri;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\ObjectExpand\StringExpand;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/12/26
 * Time: 15:02
 */
class ClientController extends Controller {

    public function indexAction() {
        $data = Request::post('grant_type,scope');
        if ($data['grant_type'] !== 'client_credentials') {
            return;
        }
        return $this->ajax([
            'access_token' => $token,
            'token_type' => '',
            'expires_in' => 3600,
            //'scope'
        ]);
    }
}