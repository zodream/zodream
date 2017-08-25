<?php
namespace Zodream\Service\Rest\OAuth\Grant;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/11/29
 * Time: 15:46
 */
class ClientCredentialsGrant extends BaseGrant {
    /**
     * {@inheritdoc}
     */
    public function getIdentifier() {
        return 'client_credentials';
    }
}