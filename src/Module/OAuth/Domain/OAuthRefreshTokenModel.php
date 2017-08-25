<?php
namespace Zodream\Module\OAuth\Domain;

/**
 * Class OauthClientModel
 * @package Zodream\Module\OAuth\Domain
 * @property string $access_token
 * @property string $client_id
 * @property string $user_id
 * @property string $expires
 * @property string $scope
 */
class OAuthRefreshTokenModel extends BaseModel {
    public static function tableName() {
        return 'oauth_refresh_token';
    }

    public static function createTable() {
        $table = static::getTable();
        $table->set('refresh_token')->varchar(40)->pk();
        $table->set('client_id')->notNull()->varchar(80);
        $table->set('user_id')->varchar(255);
        $table->set('expires')->notNull()->timestamp();
        $table->set('scope')->varchar(200);
        return $table->create();
    }
}