<?php
namespace Zodream\Module\OAuth\Domain;

/**
 * Class OauthClientModel
 * @package Zodream\Module\OAuth\Domain
 * @property integer $id
 * @property integer $client_id
 * @property integer $user_id
 * @property integer $create_at
 */
class OAuthClientUserModel extends BaseModel {
    public static function tableName() {
        return 'oauth_client_user';
    }

    public static function createTable() {
        $table = static::getTable();
        $table->set('id')->int()->pk();
        $table->set('client_id')->notNull(10)->int();
        $table->set('user_id')->notNull(10)->int();
        $table->set('create_at')->int(10);
        return $table->create();
    }
}