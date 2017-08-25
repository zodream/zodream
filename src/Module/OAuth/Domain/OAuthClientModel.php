<?php
namespace Zodream\Module\OAuth\Domain;

/**
 * Class OauthClientModel
 * @package Zodream\Module\OAuth\Domain
 * @property string $id
 * @property string $client_id
 * @property string $client_secret
 * @property string $redirect_uri
 * @property integer $user_id
 * @property integer $update_at
 * @property integer $create_at
 */
class OAuthClientModel extends BaseModel {
    public static function tableName() {
        return 'oauth_client';
    }

    public static function createTable() {
        $table = static::getTable();
        $table->set('id')->pk();
        $table->set('client_id')->varchar(80)->unique();
        $table->set('client_secret')->notNull()->varchar(80);
        $table->set('redirect_uri')->notNull()->varchar(200);
        $table->set('user_id')->notNull(10)->int();
        $table->set('update_at')->int(10);
        $table->set('create_at')->int(10);
        return $table->create();
    }
}