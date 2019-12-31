<?php declare(strict_types=1);

namespace Mei\Utilities;

use Mei\Dispatcher;

use function curl_close;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function curl_setopt_array;

/**
 * Class Curl
 *
 * @package Mei\Utilities
 */
class Curl
{
    /**
     * @var null|resource $curl
     */
    private $curl = null;

    /**
     * Curl constructor.
     *
     * @param string|null $url
     */
    public function __construct(?string $url = null)
    {
        if (is_null($url)) {
            $this->curl = curl_init();
        } else {
            $this->curl = curl_init($url);
        }
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }

    /**
     * @param int $option
     * @param $value
     *
     * @return bool
     */
    public function setopt(int $option, $value): bool
    {
        return curl_setopt($this->curl, $option, $value);
    }

    /**
     * @param int $option
     *
     * @return mixed
     */
    public function getInfo(int $option)
    {
        return curl_getinfo($this->curl, $option);
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    public function setoptArray(array $options): bool
    {
        return curl_setopt_array($this->curl, $options);
    }

    /**
     * @param bool $proxyOverride
     *
     * @return bool|string
     */
    public function exec(bool $proxyOverride = false)
    {
        if (!$proxyOverride) { // override proxy
            $this->setopt(
                CURLOPT_PROXY,
                (Dispatcher::config('site.proxy') ? Dispatcher::config('site.proxy') : null)
            );
        }
        $this->setopt(CURLOPT_TIMEOUT, Dispatcher::config('site.timeout'));

        return curl_exec($this->curl);
    }

    /**
     * @return string
     */
    public function error(): string
    {
        return curl_error($this->curl);
    }
}
