<?php

namespace Mei\Utilities;

use Exception;

class Encryption
{
    const CIPHER = 'aes-256-cbc';

    protected $di;
    protected $encryptionKey;
    protected $config;

    public function __construct($di)
    {
        $this->di = $di;
        $this->config = $di['config'];
        $this->encryptionKey = md5($this->config['api.auth_key']);
    }

    public function encrypt($plainData)
    {
        srand();

        // we need to manually pad data for compatibility with mcrypt
        $paddedData = $plainData;
        if (strlen($paddedData) % 32) {
            $paddedData = str_pad($paddedData,
                strlen($paddedData) + 32 - strlen($paddedData) % 32, "\0");
        }

        $initVector = random_bytes(16);
        $cryptoStr = openssl_encrypt($paddedData, self::CIPHER, $this->encryptionKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $initVector);

        return base64_encode($initVector . $cryptoStr);
    }

    public function decrypt($encryptedData)
    {
        if ($encryptedData != "") {
            try {
                $data = base64_decode($encryptedData);
                if ($data == false) return false;
                $initVector = substr($data, 0, 16);
                $unpaddedCryptedData = substr($data, 16);
                $r = trim(openssl_decrypt($unpaddedCryptedData, self::CIPHER, $this->encryptionKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $initVector));
                if (!$r) return false;
                return $r;
            } catch (Exception $e) {
                return false;
            }
        } else {
            return "";
        }
    }

    public function decryptString($encryptedData)
    {
        $result = $this->decrypt($encryptedData);
        if (!$result) return false;
        $isUTF8 = preg_match('//u', $result);
        if (!$isUTF8) return false;
        return $result;
    }

    public function encryptUrl($plainData)
    {
        return StringUtil::base64UrlEncode($this->encrypt($plainData));
    }

    public function decryptUrl($encryptedData)
    {
        return $this->decrypt(StringUtil::base64UrlDecode($encryptedData));
    }
}
