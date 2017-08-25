<?php
namespace Zodream\Service\Rest\OAuth\Grant;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/11/29
 * Time: 15:47
 */
use Zodream\Service\Rest\OAuth\Exception\OAuthServerException;
use Zodream\Infrastructure\Http\Request;

abstract class BaseGrant {



    /**
     * @return string
     */
    abstract public function getIdentifier();
}