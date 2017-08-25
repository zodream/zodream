<?php
namespace Zodream\Service\Rest\OAuth\Exception;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/11/29
 * Time: 16:59
 */
use Zodream\Infrastructure\Http\Response;
use Zodream\Service\Factory;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Http\Component\Uri;

class OAuthServerException extends \Exception {
    /**
     * @var int
     */
    private $httpStatusCode;

    /**
     * @var string
     */
    private $errorType;

    /**
     * @var null|string
     */
    private $hint;

    /**
     * @var null|string
     */
    private $redirectUri;

    /**
     * Throw a new exception.
     *
     * @param string      $message        Error message
     * @param int         $code           Error code
     * @param string      $errorType      Error type
     * @param int         $httpStatusCode HTTP status code to send (default = 400)
     * @param null|string $hint           A helper hint
     * @param Uri|null $redirectUri    A HTTP URI to redirect the user back to
     */
    public function __construct($message, $code, $errorType, $httpStatusCode = 400, $hint = null, $redirectUri = null) {
        parent::__construct($message, $code);
        $this->httpStatusCode = $httpStatusCode;
        $this->errorType = $errorType;
        $this->hint = $hint;
        $this->redirectUri = $redirectUri;
    }

    /**
     * Unsupported grant type error.
     *
     * @return static
     */
    public static function unsupportedGrantType() {
        $errorMessage = 'The authorization grant type is not supported by the authorization server.';
        $hint = 'Check the `grant_type` parameter';

        return new static($errorMessage, 2, 'unsupported_grant_type', 400, $hint);
    }

    /**
     * Invalid request error.
     *
     * @param string      $parameter The invalid parameter
     * @param null|string $hint
     *
     * @return static
     */
    public static function invalidRequest($parameter, $hint = null) {
        $errorMessage = 'The request is missing a required parameter, includes an invalid parameter value, ' .
            'includes a parameter more than once, or is otherwise malformed.';
        $hint = ($hint === null) ? sprintf('Check the `%s` parameter', $parameter) : $hint;

        return new static($errorMessage, 3, 'invalid_request', 400, $hint);
    }

    /**
     * Invalid client error.
     *
     * @return static
     */
    public static function invalidClient() {
        $errorMessage = 'Client authentication failed';

        return new static($errorMessage, 4, 'invalid_client', 401);
    }

    /**
     * Invalid scope error.
     *
     * @param string      $scope       The bad scope
     * @param null|string $redirectUri A HTTP URI to redirect the user back to
     *
     * @return static
     */
    public static function invalidScope($scope, $redirectUri = null) {
        $errorMessage = 'The requested scope is invalid, unknown, or malformed';
        $hint = sprintf('Check the `%s` scope', $scope);

        return new static($errorMessage, 5, 'invalid_scope', 400, $hint, $redirectUri);
    }

    /**
     * Invalid credentials error.
     *
     * @return static
     */
    public static function invalidCredentials() {
        return new static('The user credentials were incorrect.', 6, 'invalid_credentials', 401);
    }

    /**
     * Server error.
     *
     * @param $hint
     *
     * @return static
     *
     * @codeCoverageIgnore
     */
    public static function serverError($hint) {
        return new static(
            'The authorization server encountered an unexpected condition which prevented it from fulfilling'
            . ' the request: ' . $hint,
            7,
            'server_error',
            500
        );
    }

    /**
     * Invalid refresh token.
     *
     * @param null|string $hint
     *
     * @return static
     */
    public static function invalidRefreshToken($hint = null) {
        return new static('The refresh token is invalid.', 8, 'invalid_request', 400, $hint);
    }

    /**
     * Access denied.
     *
     * @param null|string $hint
     * @param null|string $redirectUri
     *
     * @return static
     */
    public static function accessDenied($hint = null, $redirectUri = null) {
        return new static(
            'The resource owner or authorization server denied the request.',
            9,
            'access_denied',
            401,
            $hint,
            $redirectUri
        );
    }

    /**
     * Invalid grant.
     *
     * @param string $hint
     *
     * @return static
     */
    public static function invalidGrant($hint = '') {
        return new static(
            'The provided authorization grant (e.g., authorization code, resource owner credentials) or refresh token '
            . 'is invalid, expired, revoked, does not match the redirection URI used in the authorization request, '
            . 'or was issued to another client.',
            10,
            'invalid_grant',
            400,
            $hint
        );
    }

    /**
     * @return string
     */
    public function getErrorType() {
        return $this->errorType;
    }

    /**
     * Generate a HTTP response.
     *
     * @param bool              $useFragment True if errors should be in the URI fragment instead of query string
     *
     * @return Response
     */
    public function generateHttpResponse($useFragment = false) {
        $this->setHttpHeaders();
        $payload = [
            'error'   => $this->getErrorType(),
            'message' => $this->getMessage(),
        ];

        if ($this->hint !== null) {
            $payload['hint'] = $this->hint;
        }

        if ($this->redirectUri !== null) {
            if ($useFragment === true) {
                $this->redirectUri->addFragment(http_build_query($payload));
            } else {
                $this->redirectUri->addData($payload);
            }
            return Factory::response()->setStatusCode(302)->sendRedirect($this->redirectUri);
        }
        return Factory::response()
            ->setStatusCode($this->getHttpStatusCode())
            ->sendJson($payload);
    }

    /**
     * Get all headers that have to be send with the error response.
     *
     */
    public function setHttpHeaders() {

        // Add "WWW-Authenticate" header
        //
        // RFC 6749, section 5.2.:
        // "If the client attempted to authenticate via the 'Authorization'
        // request header field, the authorization server MUST
        // respond with an HTTP 401 (Unauthorized) status code and
        // include the "WWW-Authenticate" response header field
        // matching the authentication scheme used by the client.
        // @codeCoverageIgnoreStart
        if ($this->errorType === 'invalid_client') {
            $authScheme = 'Basic';
            if (strpos(Request::server('HTTP_AUTHORIZATION'), 'Bearer') === 0
            ) {
                $authScheme = 'Bearer';
            }
            Factory::response()->header
                ->set('WWW-Authenticate', $authScheme . ' realm="OAuth"');
        }
    }

    /**
     * Returns the HTTP status code to send when the exceptions is output.
     *
     * @return int
     */
    public function getHttpStatusCode() {
        return $this->httpStatusCode;
    }

    /**
     * @return null|string
     */
    public function getHint() {
        return $this->hint;
    }
}