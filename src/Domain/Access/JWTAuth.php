<?php
declare(strict_types = 1);

namespace Zodream\Domain\Access;

use Firebase\JWT\JWT;
use Zodream\Database\Model\UserModel;
use Zodream\Helpers\Str;
use Zodream\Infrastructure\Interfaces\UserObject;
use Zodream\Service\Factory;
use Exception;

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

    protected $payload = false;

    /**
     * @var array
     */
    private $configs = [];

    /**
     * 获取配置
     * @param null $key
     * @param null $default
     * @return array|mixed|string
     */
    public function getConfigs($key = null, $default = null) {
        if (empty($this->configs)) {
            $this->configs = Factory::config('auth', [
                'key' => 'uZXUa9ssSS5nr1lWvjTSwYhVxBxNsAyj',
                'alg' => 'HS256',
                'refreshTTL' => 20160,
                'gracePeriod' => 0,
            ]);
        }
        if (empty($key)) {
            return $this->configs;
        }
        return isset($this->configs[$key]) ? $this->configs[$key] : $default;
    }

    /**
     * 设置配置
     * @param $key
     * @param $value
     * @return JWTAuth
     */
    public function setConfigs($key, $value = null) {
        $configs = $this->getConfigs();
        if (is_array($key)) {
            $this->configs = array_merge($configs, $value);
            return $this;
        }
        $this->configs[$key] = $value;
        return $this;
    }


    /**
     * @param mixed $payload
     */
    public function setPayload($payload) {
        $this->payload = $payload;
    }

    protected function decodePayload() {
        try {
            $configs = $this->getConfigs();
            $this->setPayload(JWT::decode($this->getToken(),
                isset($configs['publicKey']) ? $configs['publicKey']
                    : $configs['key'], [$configs['alg']]));
        } catch (Exception $ex) {
            $this->setPayload(null);
            logger(sprintf('jwt token error: %s', $ex->getMessage()));
        }
    }

    /**
     * @param null $key
     * @param null $default
     * @return mixed
     */
    public function getPayload($key = null, $default = null) {
        if ($this->payload === false) {
            $this->decodePayload();
        }
        if (empty($key)) {
            return $this->payload;
        }
        if (!empty($this->payload) && property_exists($this->payload, $key)) {
            return $this->payload->{$key};
        }
        return $default;
    }


    /**
     * 获取用户
     * @return UserObject
     * @throws \Exception
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
        $jti = static::getPayload('jti');
        if (empty($jti) || !cache()->has($jti)) {
            return null;
        }
        $time = time();
        if ($time < $this->getPayload('nbf') ||
            (!empty($this->getPayload('exp')) && $time > $this->getPayload('exp'))) {
            return null;
        }
        return call_user_func($userClass.'::findByIdentity', $this->getPayload('sub'));
    }

    /**
     * 生成一个token
     * @param UserModel $user
     * @param int $refreshTTL 刷新时间
     * @return string
     * @throws Exception
     */
    public function createToken(UserModel $user, $refreshTTL = 0): string {
        $configs = $this->getConfigs();
        $time = time();
        $payload = [
            'sub' => $user->getIdentity(),
            'iss' => url()->getHost(),
            'iat' => $time,
            'nbf' => $time,
            'jti' => Str::random(60)
        ];
        $payload['exp'] = $time + $configs['refreshTTL'] + $refreshTTL;
        cache()->set($payload['jti'], $payload, $configs['refreshTTL']);
        return JWT::encode($payload, isset($configs['privateKey']) ? $configs['privateKey']
            : $configs['key'], $configs['alg']);
    }

    /**
     * @throws \Exception
     */
    public function logout() {
        cache()->delete($this->getPayload('jti'));
        return parent::logout();
    }

}