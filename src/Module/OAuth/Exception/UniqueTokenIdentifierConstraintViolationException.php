<?php
namespace Zodream\Service\Rest\OAuth\Exception;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/11/29
 * Time: 17:30
 */
class UniqueTokenIdentifierConstraintViolationException extends OAuthServerException {
    public static function create() {
        $errorMessage = 'Could not create unique access token identifier';
        return new static($errorMessage, 100, 'access_token_duplicate', 500);
    }
}
