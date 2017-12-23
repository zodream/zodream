<?php
namespace Zodream\Domain\Access;
/**
 * AUTH CONTROL
 *
 * @author Jason
 */
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Interfaces\UserObject;
use Zodream\Service\Config;

class Token extends Auth {

    /**
     * 获取用户
     * @return UserObject
     */
    protected static function getUser() {
        $userClass = Config::auth('model');
        if (empty($userClass)) {
            return null;
        }
        $token = static::getTokenForRequest();
        if (empty($token)) {
            return null;
        }
        return call_user_func($userClass.'::findByToken', $token);
    }

    /**
     * 获取 api token
     * @return string
     */
	protected static function getTokenForRequest() {
	    $inputKey = Config::auth('api_token', 'api_token');
        $token = Request::get($inputKey);
        if (empty($token)) {
            $token = Request::request($inputKey);
        }

        if (empty($token)) {
            $token = Request::bearerToken();
        }

        if (empty($token)) {
            list(, $token) = Request::auth();
        }

        return $token;
    }
}