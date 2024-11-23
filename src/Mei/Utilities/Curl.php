<?php

declare(strict_types=1);

namespace Mei\Utilities;

use CurlHandle;
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
final class Curl
{
    public const string DEFAULT_USER_AGENT = 'mei-image-server/1.0';

    private CurlHandle $curl;

    public function __construct(?string $url = null)
    {
        $this->curl = curl_init($url);

        curl_setopt_array(
            $this->curl,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => '', // let curl decode content if supported
                CURLOPT_USERAGENT => self::DEFAULT_USER_AGENT,
                CURLOPT_TIMEOUT_MS => 3000, // 3s
                CURLOPT_PROXY => Dispatcher::config('proxy'),
                CURLOPT_HTTPPROXYTUNNEL => true
            ]
        );
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }

    public function setopt(int $option, mixed $value): bool
    {
        return curl_setopt($this->curl, $option, $value);
    }

    public function getInfo(int $option): mixed
    {
        return curl_getinfo($this->curl, $option);
    }

    public function setoptArray(array $options): bool
    {
        return curl_setopt_array($this->curl, $options);
    }

    public function exec(): bool|string
    {
        return curl_exec($this->curl);
    }

    public function error(): string
    {
        return curl_error($this->curl);
    }
}
