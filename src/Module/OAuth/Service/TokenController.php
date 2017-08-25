<?php
namespace Zodream\Module\OAuth\Service;
use Zodream\Infrastructure\Http\Component\Uri;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\ObjectExpand\StringExpand;
use Zodream\Service\Rest\OAuth\Exception\OAuthServerException;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/12/26
 * Time: 15:02
 */
class TokenController extends Controller {

    public function indexAction() {
        $grant_type = Request::post('grant_type');
        if ($grant_type !== 'authorization_code') {
            return $this->getToken();
        }
        if ($grant_type !== 'refresh_token') {
            return $this->refreshToken();
        }
        throw OAuthServerException::unsupportedGrantType();
    }

    public function getToken() {
        $data = Request::post('grant_type,code,redirect_uri,client_id');

        return $this->ajax([
            'access_token' => $token,
            'token_type' => '',
            'expires_in' => 3600,
            'refresh_token' => '',
            //'scope'
        ]);
    }

    public function refreshToken() {
        $data = Request::post('grant_type,refresh_token,scope');

        return $this->ajax([
            'access_token' => $token,
            'token_type' => '',
            'expires_in' => 3600,
            'refresh_token' => '',
            //'scope'
        ]);
    }
}