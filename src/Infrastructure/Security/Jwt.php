<?php
namespace Zodream\Infrastructure\Security;

/**

 * Implements JWT encoding and decoding as per http://tools.ietf.org/html/draft-ietf-oauth-json-web-token-06

 * Encoding algorithm based on http://code.google.com/p/google-api-php-client

 * Decoding algorithm based on https://github.com/luciferous/jwt

 * @author Francis Chuang <francis.chuang@gmail.com>

 */
use Exception;
use DomainException;
use UnexpectedValueException;

class Jwt extends BaseSecurity {
    
    protected $algo = 'HS256';
    
    protected $key;

    public function setAlgo($algo) {
        $this->algo = strtoupper($algo);
        return $this;
    }

    public function getAlgo() {
        return $this->algo;
    }

    public function setKey($key) {
        $this->key = $key;
        return $this;
    }

    public function getKey() {
        return $this->key;
    }
    
    public function encrypt($payload) {
        $header = array('typ' => 'JWT', 'alg' => $this->algo); 
        $segments = array(
            $this->urlsafeB64Encode(json_encode($header)), 
            $this->urlsafeB64Encode(json_encode($payload)) 
        );
        $signing_input = implode('.', $segments);
        $signature = $this->sign($signing_input, $this->key, $this->algo);
        $segments[] = $this->urlsafeB64Encode($signature);
        return implode('.', $segments);
    }

    public function decrypt($jwt) {
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            throw new Exception(
                __('Wrong number of segments')
            );
        }
        list($headb64, $payloadb64, $cryptob64) = $tks;
        if (null === ($header = json_decode($this->urlsafeB64Decode($headb64)))) {
            throw new Exception(
                __('Invalid segment encoding')
            );
        }
        if (null === $payload = json_decode($this->urlsafeB64Decode($payloadb64))) {
            throw new Exception(
                __('Invalid segment encoding')
            );
        }
        $sig = $this->urlsafeB64Decode($cryptob64);
        if (isset($this->key)) {
            if (empty($header->alg)) {
                throw new DomainException(
                    __('Empty algorithm')
                );
            }
            if (!$this->verifySignature($sig, "$headb64.$payloadb64", $this->key, $this->algo)) {
                throw new UnexpectedValueException(
                    __('Signature verification failed')
                );
            }
        }
        return $payload;
    }

    private function verifySignature($signature, $input, $key, $algo) {
        switch ($algo) {
            case'HS256':
            case'HS384':
            case'HS512':
                return $this->sign($input, $key, $algo) === $signature;
            case 'RS256':
                return (boolean) openssl_verify($input, $signature, $key, OPENSSL_ALGO_SHA256);
            case 'RS384':
                return (boolean) openssl_verify($input, $signature, $key, OPENSSL_ALGO_SHA384);
            case 'RS512':
                return (boolean) openssl_verify($input, $signature, $key, OPENSSL_ALGO_SHA512);
            default:
                throw new Exception(
                    __('Unsupported or invalid signing algorithm.')
                );
        }
    }

    private function sign($input, $key, $algo) {
        switch ($algo) {
            case 'HS256':
                return hash_hmac('sha256', $input, $key, true);
            case 'HS384':
                return hash_hmac('sha384', $input, $key, true);
            case 'HS512':
                return hash_hmac('sha512', $input, $key, true);
            case 'RS256':
                return $this->generateRSASignature($input, $key, OPENSSL_ALGO_SHA256);
            case 'RS384':
                return $this->generateRSASignature($input, $key, OPENSSL_ALGO_SHA384);
            case 'RS512':
                return $this->generateRSASignature($input, $key, OPENSSL_ALGO_SHA512);
            default:
                throw new Exception(
                    __('Unsupported or invalid signing algorithm.')
                );
        }
    }

    private function generateRSASignature($input, $key, $algo) { 
        if (!openssl_sign($input, $signature, $key, $algo)) { 
            throw new Exception(
                __('Unable to sign data.')
            );
        } 
        return $signature; 
    } 
    
    private function urlSafeB64Encode($data) { 
        $b64 = base64_encode($data); 
        $b64 = str_replace(array('+', '/', '\r', '\n', '='), 
            array('-', '_'),
            $b64
        ); 
        return $b64; 
    }
 
    private function urlSafeB64Decode($b64) { 
        $b64 = str_replace(array('-', '_'), 
            array('+', '/'), 
            $b64
        );
        return base64_decode($b64); 
    }
}