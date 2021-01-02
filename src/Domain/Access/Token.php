<?php
declare(strict_types = 1);

namespace Zodream\Domain\Access;
/**
 * AUTH CONTROL
 *
 * @author Jason
 */
use Zodream\Infrastructure\Contracts\UserObject;

class Token extends Auth {

    /**
     * @var string
     */
    protected $token;

    /**
     * 获取用户
     * @return UserObject
     */
    protected function getUser() {
        $userClass = config('auth.model');
        if (empty($userClass)) {
            return null;
        }
        $token = $this->getToken();
        if (empty($token)) {
            return null;
        }
        return call_user_func($userClass.'::findByToken', $token);
    }

    /**
     * @return string
     */
    public function getToken() {
        if (empty($this->token)) {
            $this->token = static::getTokenForRequest();
        }
        return $this->token;
    }

    /**
     * @param string $token
     * @return static
     */
    public function setToken(string $token) {
        $this->token = $token;
        return $this;
    }

    /**
     * 获取 api token
     * @return string
     */
	protected function getTokenForRequest() {
	    $inputKey = config('auth.api_token', 'api_token');
	    $request = $this->app->make('request');
        $token = $request->get($inputKey);
        if (empty($token)) {
            $token = $request->get($inputKey);
        }
        if (empty($token)) {
            $token = $request->bearerToken();
        }
        if (empty($token)) {
            list(, $token) = $request->auth();
        }
        return $token;
    }
}