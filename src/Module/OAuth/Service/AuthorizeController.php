<?php
namespace Zodream\Module\OAuth\Service;

use Zodream\Domain\Access\Auth;
use Zodream\Infrastructure\Http\Component\Uri;
use Zodream\Infrastructure\ObjectExpand\StringExpand;
use Zodream\Module\OAuth\Domain\OAuthClientModel;
use Zodream\Module\OAuth\Domain\OAuthClientUserModel;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/12/26
 * Time: 15:02
 */
class AuthorizeController extends Controller {

    public function indexAction(
        $response_type,
        $client_id,
        $redirect_uri,
        $scope = null,
        $state = null) {
        if ($response_type != 'code') {
            return;
        }

        $model = OAuthClientModel::findOne(['client_id' => $client_id]);
        if (empty($model)) {
            return;
        }
        if (stripos($redirect_uri, $model->redirect_uri) !== 0) {
            return;
        }
        if (Auth::guest()) {
            return $this->show();
        }

        $history = OAuthClientUserModel::count([
            'client_id' => $model->id,
            'user_id' => Auth::user()->getId()
        ]);
        if ($history < 0) {
            return $this->show();
        }

        $code = StringExpand::random();
        $uri = new Uri($redirect_uri);
        return $this->redirect($uri->addData([
            'code' => $code,
            'state' => $state
        ]));
    }

    public function loginAction() {
        return $this->ajax(array(
            'code' => 0,
            'data' => []
        ));
    }

    public function authorizeAction() {
        return $this->ajax(array(
            'code' => 0,
            'data' => ''
        ));
    }

}