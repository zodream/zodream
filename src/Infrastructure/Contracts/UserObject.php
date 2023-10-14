<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/12
 * Time: 16:08
 */
interface UserObject {

    const BEFORE_LOGIN = 'before login';
    const AFTER_LOGIN = 'after login';
    const BEFORE_LOGOUT = 'before logout';
    const AFTER_LOGOUT = 'after logout';

    /**
     * 根据账号密码登录
     * @param string $username
     * @param string $password
     * @return UserObject|null
     */
    public static function findByAccount(string $username, string $password): ?UserObject;

    /**
     * 根据 主键获取用户
     * @param int|string $id
     * @return UserObject|null
     */
    public static function findByIdentity(int|string $id): ?UserObject;

    /**
     * api 时根据 api token 获取用户
     * @param string $token
     * @return UserObject|null
     */
    public static function findByToken(string $token): ?UserObject;

    /**
     * 根据 记住密码 token 获取用户
     * @param int|string $id
     * @param string $token
     * @return UserObject|null
     */
    public static function findByRememberToken(int|string $id, string $token): ?UserObject;

    /**
     * 登录
     * @param bool $remember
     * @return void
     */
    public function login(bool $remember = false): void;

    /**
     * 注销
     * @return void
     */
    public function logout(): void;

    /**
     * 获取用户ID
     * @return int|string
     */
    public function getIdentity(): int|string;

    /**
     * @return string
     */
    public function getRememberToken(): string;

    /**
     * @param string $token
     * @return static
     */
    public function setRememberToken(string $token): UserObject;
}