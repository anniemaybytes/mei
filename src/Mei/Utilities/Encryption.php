<?php

declare(strict_types=1);

namespace Mei\Utilities;

use ArrayAccess;
use Random\RandomException;

/**
 * Class Encryption
 *
 * @package Mei\Utilities
 */
final class Encryption
{
    private const string CIPHER = 'aes-256-cbc';

    protected string $secret;

    public function __construct(ArrayAccess $config)
    {
        $this->secret = md5($config['encryption.secret']);
    }

    /***************************************************************************************************************
     * *********************************** GENERAL ENCRYPTION/DECRYPTION *******************************************
     ***************************************************************************************************************/

    /** @throws RandomException */
    public function encrypt(?string $input): string
    {
        $data = $input;
        if (strlen($data) % 32) {
            // we need to manually pad data for compatibility with mcrypt
            $data = str_pad($data, strlen($data) + 32 - strlen($data) % 32, "\0");
        }

        $iv = random_bytes(16);
        $crypt = openssl_encrypt($data, self::CIPHER, $this->secret, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

        return base64_encode($iv . $crypt);
    }

    public function decrypt(?string $input): string
    {
        if (!is_string($input) || $input === '') {
            return '';
        }

        $data = base64_decode($input, true);
        if ($data === false || strlen($data) < 16) {
            return '';
        }

        $iv = substr($data, 0, 16);
        $crypt = substr($data, 16);

        $str = openssl_decrypt($crypt, self::CIPHER, $this->secret, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

        return trim($str === false ? '' : $str);
    }

    public function decryptString(string $input): string
    {
        $result = $this->decrypt($input);
        return preg_match('//u', $result) ? $result : '';
    }

    public static function secureCompare(string $alpha, string $beta): bool
    {
        return hash_equals($alpha, $beta);
    }

    /***************************************************************************************************************
     * ******************************************* URL ENCRYPTION/DECRYPTION ***************************************
     ***************************************************************************************************************/

    /** @throws RandomException */
    public function encryptUrl(string $input): string
    {
        return StringUtil::base64UrlEncode($this->encrypt($input));
    }

    public function decryptUrl(string $input): string
    {
        return $this->decrypt(StringUtil::base64UrlDecode($input));
    }

    /***************************************************************************************************************
     * **************************************************** HMAC ***************************************************
     ***************************************************************************************************************/

    public function generateHmac(string $data, string $algo = 'sha256'): string
    {
        return hash_hmac($algo, $data, $this->secret);
    }

    public function hmacValid(string $data, string $hmac, string $algo = 'sha256'): bool
    {
        return self::secureCompare($hmac, $this->generateHmac($data, $algo));
    }
}
