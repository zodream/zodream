<?php
namespace Zodream\Service\Rest\OAuth\Grant;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/11/29
 * Time: 15:46
 */
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Http\Component\Uri;

class AuthCodeGrant extends BaseGrant {

    public function authorizationCode() {
        if (Request::get('response_type') != 'code') {
            return false;
        }
        $redirect_uri = Request::get('redirect_uri');
        $state = Request::get('state');
        $scope = Request::get('scope');

        return (new Uri($redirect_uri))->addData([
            'state' => $state,
            'code' => ''
        ]);
    }

    public function accessToken() {

    }


    /**
     * Return the grant identifier that can be used in matching up requests.
     *
     * @return string
     */
    public function getIdentifier() {
        return 'authorization_code';
    }
}