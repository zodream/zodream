<?php
namespace Zodream\Domain\Access;

use Firebase\JWT\JWT;
use Zodream\Database\Model\UserModel;
use Zodream\Helpers\Str;
use Zodream\Infrastructure\Http\Request;
use Zodream\Service\Factory;

/**
 * Class JWTAuth
 * payload:
sub: 该JWT所面向的用户
iss: 该JWT的签发者
iat(issued at): 在什么时候签发的token
exp(expires): token什么时候过期
nbf(not before)：token在此时间之前不能被接收处理
jti：JWT ID为web token提供唯一标识
 * @package Zodream\Domain\Access
 */
class JWTAuth extends Token {

    protected static $payload = [];

    protected static $token;

    protected static function getConfigs() {
        return Factory::config('auth', [
            'key' => 'uZXUa9ssSS5nr1lWvjTSwYhVxBxNsAyj',
            'alg' => 'HS256',
            'refreshTTL' => 20160,
            'gracePeriod' => 0,
        ]);
    }

    /**
     * @param mixed $token
     */
    public static function setToken($token) {
        static::$token = $token;
    }

    /**
     * @return mixed
     */
    public static function getToken() {
        if (empty(static::$token)) {
            static::$token = static::getTokenForRequest();
        }
        return static::$token;
    }


    /**
     * @param mixed $payload
     */
    public static function setPayload(array $payload) {
        static::$payload = $payload;
    }

    /**
     * @param null $key
     * @param null $default
     * @return mixed
     */
    public static function getPayload($key = null, $default = null) {
        if (empty(static::$payload)) {
            $configs = static::getConfigs();
            static::setPayload(JWT::decode(static::getToken(),
                isset($configs['publicKey']) ? $configs['publicKey']
                : $configs['key'], [$configs['alg']]));
        }
        if (empty($key)) {
            return static::$payload;
        }
        if (array_key_exists($key, (array)static::$payload)) {
            return static::$payload[$key];
        }
        return $default;
    }



    /**
     * 获取用户
     * @return UserObject|null
     */
    protected static function getUser() {
        $userClass = Config::auth('model');
        if (empty($userClass)) {
            return null;
        }
        $token = static::getToken();
        if (empty($token)) {
            return null;
        }
        if (!Factory::cache()->has(static::getPayload('jti'))) {
            return null;
        }
        $time = time();
        if ($time < static::$payload['nbf'] ||
            (isset(static::$payload['exp']) && $time > static::$payload['exp'])) {
            return null;
        }
        return call_user_func($userClass.'::findByIdentity', static::$payload['sub']);
    }

    /**
     * 生成一个token
     * @param UserModel $user
     * @return string
     */
    public static function createToken(UserModel $user) {
        $configs = static::getConfigs();
        $time = time();
        $payload = [
            'sub' => $user->getIdentity(),
            'iss' => Request::url(),
            'iat' => $time,
            'nbf' => $time,
            'jti' => Str::random(60)
        ];
        if ($configs['refreshTTL'] > 0) {
            $payload['exp'] = $time + $configs['refreshTTL'];
        }
        Factory::cache()->set($payload['jti'], $payload, $configs['refreshTTL']);
        return JWT::encode($payload, isset($configs['privateKey']) ? $configs['privateKey']
            : $configs['key'], $configs['alg']);
    }

    public static function logout() {
        Factory::cache()->delete(static::getPayload('jti'));
        return parent::logout();
    }

}