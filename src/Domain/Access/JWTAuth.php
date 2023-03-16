<?php
declare(strict_types = 1);

namespace Zodream\Domain\Access;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Zodream\Database\Model\UserModel;
use Zodream\Helpers\Str;
use Zodream\Infrastructure\Caching\Cache;
use Zodream\Infrastructure\Contracts\UserObject;
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

    protected mixed $payload = false;

    /**
     * @var array
     */
    private array $configs = [];

    private $cacheDriver = null;

    /**
     * 获取配置
     * @param null $key
     * @param null $default
     * @return array|mixed|string
     */
    public function getConfigs($key = null, $default = null) {
        if (empty($this->configs)) {
            $this->configs = config('auth', [
                'key' => 'uZXUa9ssSS5nr1lWvjTSwYhVxBxNsAyj',
                'alg' => 'HS256',
                'refreshTTL' => 20160,  // 以秒为时间
                'TTL' => 20160,
                'gracePeriod' => 0,
                'cacheStore' => 'auth'
            ]);
        }
        if (empty($key)) {
            return $this->configs;
        }
        return $this->configs[$key] ?? $default;
    }

    /**
     * @return Cache
     * @throws Exception
     */
    public function cacheDriver() {
        if (empty($this->cacheDriver)) {
            $this->cacheDriver = cache()->store($this->getConfigs('cacheStore'));
        }
        return $this->cacheDriver;
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
            $raw = JWT::decode($this->getToken(), new Key($configs['publicKey'] ?? $configs['key'], $configs['alg']));
            $this->setPayload($raw);
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
        $jti = $this->getPayload('jti');
        if (empty($jti) || !$this->cacheDriver()->has($jti)) {
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
     * 刷新一个过期的token生成新的token
     * @param int $refreshTTL
     * @return string
     * @throws Exception
     */
    public function refreshToken(int $refreshTTL = 0): string {
        $token = $this->getToken();
        if (empty($token)) {
            return '';
        }
        $jti = $this->getPayload('jti');
        if (empty($jti) || !$this->cacheDriver()->has($jti)) {
            return '';
        }
        $this->cacheDriver()->delete($this->getPayload('jti'));
        $time = time();
        if ($time < $this->getPayload('nbf')) {
            return '';
        }
        $exp = intval($this->getPayload('exp')) + $this->getConfigs('refreshTTL');
        if ($exp < $time) {
            return '';
        }
        return $this->createPayloadToken([
            'sub' => $this->getPayload('sub'),
            'iss' => $this->getPayload('iss'),
            'iat' => $time,
            'nbf' => $time,
            'jti' => Str::random(60),
        ], $refreshTTL);
    }

    /**
     * 生成一个token
     * @param UserModel $user
     * @param int $refreshTTL 刷新时间
     * @return string
     * @throws Exception
     */
    public function createToken(UserModel $user, int $refreshTTL = 0): string {
        $time = time();
        $payload = [
            'sub' => $user->getIdentity(),
            'iss' => $this->app->make('request')->host(),
            'iat' => $time,
            'nbf' => $time,
            'jti' => Str::random(60)
        ];
        return $this->createPayloadToken($payload, $refreshTTL);
    }

    protected function createPayloadToken(array $payload, $refreshTTL = 0): string {
        $configs = $this->getConfigs();
        $time = time();
        $payload['exp'] = $time + $configs['TTL'] + $refreshTTL;
        $this->cacheDriver()
            ->set($payload['jti'], $payload, $configs['TTL'] + $refreshTTL + $configs['refreshTTL']);
        return JWT::encode($payload, $configs['privateKey'] ?? $configs['key'], $configs['alg']);
    }

    protected function setRememberToken(UserObject $user) {}

    protected function setRememberTokenFromUser(UserObject $user, $token) {}

    /**
     * @throws \Exception
     */
    public function logout() {
        $this->cacheDriver()->delete($this->getPayload('jti'));
        parent::logout();
    }

}