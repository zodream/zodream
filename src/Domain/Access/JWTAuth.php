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
class JWTAuth extends Auth {

    protected static function getConfigs() {
        return Factory::config('auth', [
            'key' => 'uZXUa9ssSS5nr1lWvjTSwYhVxBxNsAyj',
            'alg' => 'HS256',
            'refreshTTL' => 20160,
            'gracePeriod' => 0,
        ]);
    }

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
        $configs = static::getConfigs();
        $payload = JWT::decode($token, isset($configs['publicKey']) ? $configs['publicKey']
            : $configs['key'], [$configs['alg']]);
        if (!Factory::cache()->has($payload['jti'])) {
            return null;
        }
        $time = time();
        if ($time < $payload['nbf'] ||
            (isset($payload['exp']) && $time > $payload['exp'])) {
            return null;
        }
        return call_user_func($userClass.'::findByIdentity', $payload['sub']);
    }

    /**
     * 生成一个token
     * @param UserModel $user
     * @return string
     */
    public static function getToken(UserModel $user) {
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

}