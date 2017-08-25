<?php
namespace Zodream\Module\OAuth\Domain;

/**
 * Class OauthClientModel
 * @package Zodream\Module\OAuth\Domain
 * @property string $authorization_code
 * @property string $client_id
 * @property string $redirect_uri
 * @property string $user_id
 * @property string $expires
 * @property string $scope
 */
class OAuthAuthorizationCodeModel extends BaseModel {
    public static function tableName() {
        return 'oauth_authorization_code';
    }

    public static function createTable() {
        $table = static::getTable();
        $table->set('authorization_code')->varchar(40)->pk();
        $table->set('client_id')->notNull()->varchar(80);
        $table->set('user_id')->varchar(255);
        $table->set('redirect_uri')->varchar(200);
        $table->set('expires')->notNull()->timestamp();
        $table->set('scope')->varchar(200);
        return $table->create();
    }
}