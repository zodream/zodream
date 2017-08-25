<?php
namespace Zodream\Service\Rest\OAuth\Grant;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/11/29
 * Time: 15:46
 */
class ImplicitGrant extends BaseGrant {
    /**
     * Return the grant identifier that can be used in matching up requests.
     *
     * @return string
     */
    public function getIdentifier() {
        return 'implicit';
    }
}