<?php
namespace Zodream\Module\OAuth\Service;

use Zodream\Service\Config;
use Zodream\Service\Controller\Controller as BaseController;
use Zodream\Service\Factory;
use Zodream\Service\Rest\OAuth\Exception\OAuthServerException;

abstract class Controller extends BaseController {

    protected function validateClient() {
        list($basicAuthUser, $basicAuthPassword) = $this->getBasicAuthCredentials();
        $clientId = Request::request('client_id', $basicAuthUser);
        if (is_null($clientId)) {
            throw OAuthServerException::invalidRequest('client_id');
        }

        // If the client is confidential require the client secret
        $clientSecret = Request::request('client_secret', $basicAuthPassword);

        // If a redirect URI is provided ensure it matches what is pre-registered
        $redirectUri = Request::request('redirect_uri', null);
    }

    protected function getBasicAuthCredentials() {
        $header = Request::header('Authorization');
        if (empty($header)) {
            return [null, null];
        }
        if (is_array($header)) {
            $header = current($header);
        }
        if (strpos($header, 'Basic ') !== 0) {
            return [null, null];
        }
        if (!($decoded = base64_decode(substr($header, 6)))) {
            return [null, null];
        }
        if (strpos($decoded, ':') === false) {
            return [null, null]; // HTTP Basic header without colon isn't valid
        }
        return explode(':', $decoded, 2);
    }

    protected function validateRedirectUri($inputUri, $registeredUriString) {
        if (empty($inputUri) || empty($registeredUriString)) {
            return false;
        }

        $registered_uris = preg_split('/\s+/', $registeredUriString);
        foreach ($registered_uris as $registered_uri) {
            if (Config::getValue('require_exact_redirect_uri')) {
                if (strcmp($inputUri, $registered_uri) === 0) {
                    return true;
                }
            } else {
                if (strcasecmp(substr($inputUri, 0,
                        strlen($registered_uri)), $registered_uri) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}