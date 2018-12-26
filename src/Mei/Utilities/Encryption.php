<?php

namespace Mei\Utilities;

class Encryption
{
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
        $paddedData = str_pad($plainData, 32 - strlen($plainData));
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $initVector = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        $cryptoStr = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->encryptionKey, $paddedData, MCRYPT_MODE_CBC, $initVector);

        return base64_encode($initVector . $cryptoStr);
    }

    public function decrypt($encryptedData)
    {
        if ($encryptedData != "") {
            try {
                $data = base64_decode($encryptedData);
                if ($data == false) return false;
                $initVector = substr($data,0,16);
                $unpaddedCryptedData = substr($data,16);
                $r = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->encryptionKey, $unpaddedCryptedData, MCRYPT_MODE_CBC,$initVector));
                if(!$r) return false;
                return $r;
            } catch(\Exception $e) { return false; }
        } else {
            return "";
        }
    }

    public function decryptString($encryptedData)
    {
        $result = $this->decrypt($encryptedData);
        if(!$result) return false;
        $isUTF8 = preg_match('//u', $result);
        if(!$isUTF8) return false;
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
