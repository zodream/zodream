<?php
declare(strict_types=1);
namespace Zodream\Domain\Access\SSO;

use Exception;
use Zodream\Infrastructure\Caching\Cache;

/**
 * Single sign-on server.
 *
 * The SSO server is responsible of managing users sessions which are available for brokers.
 *
 * To use the SSO server, extend this class and implement the abstract methods.
 * This class may be used as controller in an MVC application.
 */
abstract class Server {
    /**
     * @var array
     */
    protected array $options = ['files_cache_directory' => '/tmp', 'files_cache_ttl' => 36000];

    /**
     * Cache that stores the special session data for the brokers.
     *
     * @var Cache
     */
    protected Cache $cache;

    /**
     * @var string
     */
    protected $returnType;

    /**
     * @var mixed
     */
    protected $brokerId;


    /**
     * Class constructor
     *
     * @param array $options
     */
    public function __construct(array $options = []) {
        $this->options = $options + $this->options;
        $this->cache = $this->createCacheAdapter();
    }

    /**
     * Create a cache to store the broker session id.
     *
     * @return Cache
     */
    protected function createCacheAdapter() {
        return \cache()->store('sso');
    }

    /**
     * Start the session for broker requests to the SSO server
     */
    public function startBrokerSession(): void {
        if (isset($this->brokerId)) return;

        $sid = $this->getBrokerSessionID();

        if ($sid === false) {
            $this->fail("Broker didn't send a session key", 400);
            return;
        }

        $linkedId = $this->cache->get($sid);

        if (!$linkedId) {
            $this->fail("The broker session id isn't attached to a user session", 403);
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            if ($linkedId !== session_id()) throw new \Exception("Session has already started", 400);
            return;
        }

        session_id($linkedId);
        session_start();

        $this->brokerId = $this->validateBrokerSessionId($sid);
    }

    /**
     * Get session ID from header Authorization or from $_GET/$_POST
     */
    protected function getBrokerSessionID() {
        $headers = getallheaders();

        if (isset($headers['Authorization']) && str_starts_with($headers['Authorization'], 'Bearer')) {
            $headers['Authorization'] = substr($headers['Authorization'], 7);
            return $headers['Authorization'];
        }
        if (isset($_GET['access_token'])) {
            return $_GET['access_token'];
        }
        if (isset($_POST['access_token'])) {
            return $_POST['access_token'];
        }
        if (isset($_GET['sso_session'])) {
            return $_GET['sso_session'];
        }

        return false;
    }

    /**
     * Validate the broker session id
     *
     * @param string $sid session id
     * @return string|null the broker id
     * @throws Exception
     */
    protected function validateBrokerSessionId(string $sid): ?string {
        $matches = null;

        if (!preg_match('/^SSO-(\w*+)-(\w*+)-([a-z0-9]*+)$/', $this->getBrokerSessionID(), $matches)) {
            $this->fail("Invalid session id");
            return null;
        }

        $brokerId = $matches[1];
        $token = $matches[2];

        if ($this->generateSessionId($brokerId, $token) != $sid) {
            $this->fail("Checksum failed: Client IP address may have changed", 403);
            return null;
        }

        return $brokerId;
    }

    /**
     * Start the session when a user visits the SSO server
     */
    protected function startUserSession(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    }

    /**
     * Generate session id from session token
     *
     * @param string $brokerId
     * @param string $token
     * @return string
     */
    protected function generateSessionId(string $brokerId, string $token): string {
        $broker = $this->getBrokerInfo($brokerId);

        if (!isset($broker)) {
            return '';
        }

        return "SSO-{$brokerId}-{$token}-" . hash('sha256', 'session' . $token . $broker['secret']);
    }

    /**
     * Generate session id from session token
     *
     * @param string $brokerId
     * @param string $token
     * @return string
     */
    protected function generateAttachChecksum(string $brokerId, string $token): string {
        $broker = $this->getBrokerInfo($brokerId);

        if (!isset($broker)) return '';

        return hash('sha256', 'attach' . $token . $broker['secret']);
    }


    /**
     * Detect the type for the HTTP response.
     * Should only be done for an `attach` request.
     */
    protected function detectReturnType() {
        if (!empty($_GET['return_url'])) {
            $this->returnType = 'redirect';
        } elseif (!empty($_GET['callback'])) {
            $this->returnType = 'jsonp';
        } elseif (str_contains($_SERVER['HTTP_ACCEPT'], 'image/')) {
            $this->returnType = 'image';
        } elseif (str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            $this->returnType = 'json';
        }
    }

    /**
     * Attach a user session to a broker session
     */
    public function attach(): void {
        $this->detectReturnType();

        if (empty($_REQUEST['broker'])) {
            $this->fail("No broker specified", 400);
            return;
        }
        if (empty($_REQUEST['token'])) {
            $this->fail("No token specified", 400);
            return;
        }

        if (!$this->returnType) {
            $this->fail("No return url specified", 400);
            return;
        }

        $checksum = $this->generateAttachChecksum($_REQUEST['broker'], $_REQUEST['token']);

        if (empty($_REQUEST['checksum']) || $checksum != $_REQUEST['checksum']) {
            $this->fail("Invalid checksum", 400);
            return;
        }

        $this->startUserSession();
        $sid = $this->generateSessionId($_REQUEST['broker'], $_REQUEST['token']);

        $this->cache->set($sid, $this->getSessionData('id'));
        $this->outputAttachSuccess();
    }

    /**
     * Output on a successful attach
     */
    protected function outputAttachSuccess(): void {
        if ($this->returnType === 'image') {
            $this->outputImage();
        }

        if ($this->returnType === 'json') {
            header('Content-type: application/json; charset=UTF-8');
            echo json_encode(['success' => 'attached']);
        }

        if ($this->returnType === 'jsonp') {
            $data = json_encode(['success' => 'attached']);
            echo $_REQUEST['callback'] . "($data, 200);";
        }

        if ($this->returnType === 'redirect') {
            $url = $_REQUEST['return_url'];
            header("Location: $url", true, 307);
            echo "You're being redirected to <a href='{$url}'>$url</a>";
        }
    }

    /**
     * Output a 1x1px transparent image
     */
    protected function outputImage() {
        header('Content-Type: image/png');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQ'
            . 'MAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZg'
            . 'AAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
    }


    /**
     * Authenticate
     */
    public function login() {
        $this->startBrokerSession();

        if (empty($_POST['username'])) {
            $this->fail("No username specified", 400);
        }
        if (empty($_POST['password'])) {
            $this->fail("No password specified", 400);
        }

        $validation = $this->authenticate($_POST['username'], $_POST['password']);

        if (!$validation) {
            $this->fail('username or password error', 400);
            return;
        }

        $this->setSessionData('sso_user', $_POST['username']);
        $this->userInfo();
    }

    /**
     * Log out
     */
    public function logout() {
        $this->startBrokerSession();
        $this->setSessionData('sso_user', null);

        header('Content-type: application/json; charset=UTF-8');
        http_response_code(204);
    }

    /**
     * Ouput user information as json.
     */
    public function userInfo() {
        $this->startBrokerSession();
        $user = null;

        $username = $this->getSessionData('sso_user');

        if ($username) {
            $user = $this->getUserInfo($username);
            if (!$user) {
                $this->fail("User not found", 500); // Shouldn't happen
            }
        }

        header('Content-type: application/json; charset=UTF-8');
        echo json_encode($user);
    }


    /**
     * Set session data
     *
     * @param string $key
     * @param string $value
     */
    protected function setSessionData($key, $value) {
        if (!isset($value)) {
            unset($_SESSION[$key]);
            return;
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Get session data
     *
     * @param string $key
     * @return null|string
     */
    protected function getSessionData($key) {
        if ($key === 'id') return session_id();

        return $_SESSION[$key] ?? null;
    }


    /**
     * An error occured.
     *
     * @param string $message
     * @param int    $httpStatus
     */
    protected function fail(string $message, int $httpStatus = 500) {
        if (!empty($this->options['fail_exception'])) {
            throw new Exception($message, $httpStatus);
        }

        if ($httpStatus === 500) {
            trigger_error($message, E_USER_WARNING);
        }

        if ($this->returnType === 'jsonp') {
            echo $_REQUEST['callback'] . "(" . json_encode(['error' => $message]) . ", $httpStatus);";
            exit();
        }

        if ($this->returnType === 'redirect') {
            $url = $_REQUEST['return_url'] . '?sso_error=' . $message;
            header("Location: $url", true, 307);
            echo "You're being redirected to <a href='{$url}'>$url</a>";
            exit();
        }

        header('Content-type: application/json; charset=UTF-8');
        response()
            ->statusCode($httpStatus)
            ->json(['error' => $message])->send();
    }


    /**
     * Authenticate using user credentials
     *
     * @param string $username
     * @param string $password
     * @return boolean
     */
    abstract protected function authenticate(string $username, string $password): bool;

    /**
     * Get the secret key and other info of a broker
     *
     * @param string $brokerId
     * @return array
     */
    abstract protected function getBrokerInfo(string $brokerId): array;

    /**
     * Get the information about a user
     *
     * @param string $username
     * @return array
     */
    abstract protected function getUserInfo(string $username): array;
}

