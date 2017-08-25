<?php
namespace Zodream\Domain\ThirdParty\OAuth;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/9/8
 * Time: 9:44
 */
class PayPal extends BaseOAuth {

    const LIVE = 'live';
    const SANDBOX = 'sandbox';

    protected $configKey = 'paypal';

    protected $baseUrl = [
        self::LIVE => 'https://www.paypal.com/',
        self::SANDBOX => 'https://www.sandbox.paypal.com/'
     ];

    protected $apiMap = [
        'login' => [
            'signin/authorize',
            [
                '#client_id',
                'response_type' => 'code',
                'scope' => 'openid profile address email phone https://uri.paypal.com/services/paypalattributes https://uri.paypal.com/services/expresscheckout',
                '#redirect_uri',
                'nonce',
                'state'
            ]
        ],
        'access' => [
            'webapps/auth/protocol/openidconnect/v1/identity/tokenservice',
            [
                'grant_type' => 'authorization_code',
                '#code',
                '#redirect_uri'
            ],
            'POST'
        ],
        'refresh' => [
            'webapps/auth/protocol/openidconnect/v1/identity/tokenservice',
            [
                'grant_type' => 'refresh_token',
                '#refresh_token',
                'scope'
            ]
        ],
        'info' => [
            'webapps/auth/protocol/openidconnect/v1/identity/openidconnect/userinfo',
            [
                'schema' => 'openid',
                '#access_token'
            ]
        ]
    ];

    /**
     *
     * @var bool
     */
    protected $mode = self::SANDBOX;

    public function __construct(array $config = array()) {
        parent::__construct($config);
        $this->http->setOption(array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_SSLVERSION => 3
        ));
        $this->setMode($this->get('mode', self::SANDBOX));
    }

    /**
     * IS TEST OR LIVE
     * @param string $arg
     * @return $this
     */
    public function setMode($arg) {
        $this->mode = strtolower($arg) == self::LIVE ? self::LIVE : self::SANDBOX;
        return $this;
    }

    public function getBaseUrl() {
        return $this->baseUrl[$this->mode];
    }

    public function callback() {
        if (!parent::callback()) {
            return false;
        }
        return $this->getAccess();
    }

    /**
     * GET ACCESS
     * @return array
     */
    public function getAccess() {
        $this->http->setDefaultOption([
            CURLOPT_VERBOSE        => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => FALSE
        ]);
        $this->http->addHeaders(array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode($this->get('client_id') .
                    ':' . $this->get('client_secret'))
        ));

        $access = $this->getJson('access');
        $this->set($access);
        return $access;
    }

    /**
     * 获取用户信息
     * @return array|false
     */
    public function getInfo() {
        $this->http->addHeaders(array(
            'Authorization' => "Bearer " . $this->get('access_token'),
            'Content-Type' => 'x-www-form-urlencoded'
        ));
        $user = $this->getJson('info');
        if (!is_array($user) || !array_key_exists('user_id', $user)) {
            return false;
        }
        $user['username'] = $user['name'];
        $user['avatar'] = $user['picture'];
        $user['sex'] = $user['gender'];
        $this->set($user);
        return $user;
    }
}