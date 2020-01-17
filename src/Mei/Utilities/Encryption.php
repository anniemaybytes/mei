<?php declare(strict_types=1);

namespace Mei\Utilities;

use DI\Container;
use Exception;
use Tracy\Debugger;

/**
 * Class Encryption
 *
 * @package Mei\Utilities
 */
class Encryption
{
    private const CIPHER = 'aes-256-cbc';

    protected $di;
    protected $encryptionKey;
    protected $config;

    /**
     * Encryption constructor.
     *
     * @param Container $di
     */
    public function __construct(Container $di)
    {
        $this->di = $di;
        $this->config = $di->get('config');
        $this->encryptionKey = md5($this->config['api.auth_key']);
    }

    /**
     * @param string|null $plainData
     *
     * @return string
     * @throws Exception
     */
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

    /**
     * @param string|null $encryptedData
     *
     * @return string
     */
    public function decrypt(?string $encryptedData): string
    {
        if (!is_string($encryptedData) || !strlen($encryptedData)) {
            return "";
        }
        try {
            $data = base64_decode($encryptedData);
            if ($data === false || !strlen($data)) {
                return "";
            }
            $initVector = substr($data, 0, 16) ?? "";
            $unpaddedCryptedData = substr($data, 16) ?? "";
            $decryptedData = openssl_decrypt(
                    $unpaddedCryptedData,
                    $this->cipher,
                    $this->encryptionKey,
                    OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
                    $initVector
                ) ?? "";
            $r = trim($decryptedData);
            if (!strlen($r)) {
                return "";
            }
            return $r;
        } catch (Exception $e) { // must not throw exception
            Debugger::log($e, Debugger::EXCEPTION);
            return "";
        }
    }

    /**
     * @param string $encryptedData
     *
     * @return string
     */
    public function decryptString(string $encryptedData): string
    {
        $result = $this->decrypt($encryptedData);
        $isUTF8 = preg_match('//u', $result);
        if (!$isUTF8) {
            return "";
        }
        return $result;
    }

    /**
     * @param string $plainData
     *
     * @return string
     * @throws Exception
     */
    public function encryptUrl(string $plainData): string
    {
        return StringUtil::base64UrlEncode($this->encrypt($plainData));
    }

    /**
     * @param string $encryptedData
     *
     * @return string
     */
    public function decryptUrl($encryptedData): string
    {
        return $this->decrypt(StringUtil::base64UrlDecode($encryptedData));
    }
}
