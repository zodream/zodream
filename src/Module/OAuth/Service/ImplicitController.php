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
class ImplicitController extends Controller {

    public function indexAction(
        $response_type,
        $client_id,
        $redirect_uri,
        $scope = null,
        $state = null) {
        if ($response_type != 'token') {
            return;
        }

        $code = StringExpand::random();
        $uri = new Uri($redirect_uri);
        return $this->redirect($uri->addData([
            'access_token' => $token,
            'token_type' => '',
            'expires_in' => 3600,
            'state' => $state
        ]));
    }
}