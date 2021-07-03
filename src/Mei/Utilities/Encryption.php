<?php

declare(strict_types=1);

namespace Mei\Utilities;

/**
 * Class Encryption
 *
 * @package Mei\Utilities
 */
final class Encryption
{
    private const CIPHER = 'aes-256-cbc';

    protected string $encryptionKey;

    public function __construct(array $config)
    {
        $this->encryptionKey = md5($config['api.auth_key']);
    }

    public function encrypt(?string $plainData): string
    {
        // we need to manually pad data for compatibility with mcrypt
        $paddedData = $plainData;
        if (strlen($paddedData) % 32) {
            $paddedData = str_pad(
                $paddedData,
                strlen($paddedData) + 32 - strlen($paddedData) % 32,
                "\0"
            );
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $initVector = random_bytes(16);
        $cryptoStr = openssl_encrypt(
            $paddedData,
            self::CIPHER,
            $this->encryptionKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $initVector
        );

        return base64_encode($initVector . $cryptoStr);
    }

    public function decrypt(?string $encryptedData): string
    {
        if (!is_string($encryptedData) || $encryptedData === '') {
            return '';
        }

        $data = base64_decode($encryptedData, true);
        if ($data === false || strlen($data) < 16) {
            return '';
        }
        $initVector = substr($data, 0, 16);
        $unpaddedCryptedData = substr($data, 16);
        $decryptedData = openssl_decrypt(
            $unpaddedCryptedData,
            self::CIPHER,
            $this->encryptionKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $initVector
        );
        return trim($decryptedData === false ? '' : $decryptedData);
    }

    public function decryptString(string $encryptedData): string
    {
        $result = $this->decrypt($encryptedData);
        return preg_match('//u', $result) ? $result : '';
    }

    public function encryptUrl(string $plainData): string
    {
        return StringUtil::base64UrlEncode($this->encrypt($plainData));
    }

    public function decryptUrl(string $encryptedData): string
    {
        return $this->decrypt(StringUtil::base64UrlDecode($encryptedData));
    }
}
