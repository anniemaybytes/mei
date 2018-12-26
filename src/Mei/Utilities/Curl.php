<?php
namespace Mei\Utilities;

class Curl
{
    private $curl = null;

    public function __construct($url = null)
    {
        $this->curl = \curl_init($url);
    }

    public function __destruct()
    {
        \curl_close($this->curl);
    }

    public function setopt($option, $value)
    {
        return \curl_setopt($this->curl, $option, $value);
    }

    public function getinfo($option)
    {
      return \curl_getinfo($this->curl, $option);
    }

    public function setoptArray($options)
    {
        return \curl_setopt_array($this->curl, $options);
    }

    public function exec($proxyOverride = false)
    {
        if(!$proxyOverride) { // override proxy
            $this->setopt(CURLOPT_PROXY,
                (\Mei\Dispatcher::config('site.proxy') ? \Mei\Dispatcher::config('site.proxy') : null)
            );
        }
        $this->setopt(CURLOPT_TIMEOUT, \Mei\Dispatcher::config('site.timeout'));

        return \curl_exec($this->curl);
    }

    public function error()
    {
        return \curl_error($this->curl);
    }
}
