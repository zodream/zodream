<?php
namespace Zodream\Infrastructure\Security;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/9/2
 * Time: 14:57
 */
abstract class BaseSecurity {

    public function pkcs5Pad($text, $blockSize) {
        $pad = $blockSize - (strlen($text) % $blockSize);
        return $text . str_repeat(chr($pad), $pad);
    }

    public function pkcs5UnPad($text) {
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text)) return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
        return substr($text, 0, -1 * $pad);
    }

    /**
     * @url http://stackoverflow.com/questions/7314901/how-to-add-remove-pkcs7-padding-from-an-aes-encrypted-string
     * Right-pads the data string with 1 to n bytes according to PKCS#7,
     * where n is the block size.
     * The size of the result is x times n, where x is at least 1.
     *
     * The version of PKCS#7 padding used is the one defined in RFC 5652 chapter 6.3.
     * This padding is identical to PKCS#5 padding for 8 byte block ciphers such as DES.
     *
     * @param string $text the plaintext encoded as a string containing bytes
     * @param integer $blockSize the block size of the cipher in bytes
     * @return string the padded plaintext
     */
    public function pkcs7Pad($text, $blockSize) {
        $padSize = $blockSize - (strlen($text) % $blockSize);
        return $text . str_repeat(chr($padSize), $padSize);
    }

    /**
     * Validates and unpads the padded plaintext according to PKCS#7.
     * The resulting plaintext will be 1 to n bytes smaller depending on the amount of padding,
     * where n is the block size.
     *
     * The user is required to make sure that plaintext and padding oracles do not apply,
     * for instance by providing integrity and authenticity to the IV and ciphertext using a HMAC.
     *
     * Note that errors during uppadding may occur if the integrity of the ciphertext
     * is not validated or if the key is incorrect. A wrong key, IV or ciphertext may all
     * lead to errors within this method.
     *
     * The version of PKCS#7 padding used is the one defined in RFC 5652 chapter 6.3.
     * This padding is identical to PKCS#5 padding for 8 byte block ciphers such as DES.
     *
     * @param string $padded the padded plaintext encoded as a string containing bytes
     * @param integer $blockSize the block size of the cipher in bytes
     * @return string the unpadded plaintext
     * @throws \Exception
     */
    public function pkcs7UnPad($padded, $blockSize) {
        $l = strlen($padded);
        if ($l % $blockSize != 0) {
            throw new \Exception(
                __('Padded plaintext cannot be divided by the block size')
            );
        }
        $padSize = ord($padded[$l - 1]);
        if ($padSize === 0) {
            throw new \Exception(
                __('Zero padding found instead of PKCS#7 padding')
            );
        }
        if ($padSize > $blockSize) {
            throw new \Exception(
                __('Incorrect amount of PKCS#7 padding for blocksize')
            );
        }
        // check the correctness of the padding bytes by counting the occurance
        $padding = substr($padded, -1 * $padSize);
        if (substr_count($padding, chr($padSize)) != $padSize) {
            throw new \Exception(
                __('Invalid PKCS#7 padding encountered')
            );
        }
        return substr($padded, 0, $l - $padSize);
    }

    /**
     * ENCRYPT STRING
     * @param string $data
     * @return string
     */
    abstract public function encrypt($data);

    /**
     * DECRYPT STRING
     * @param string $data
     * @return string
     */
    abstract public function decrypt($data);
}