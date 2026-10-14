<?php

namespace SendKuy\Laravel;

use Psr\Http\Client\ClientInterface;
use SendKuy\Exceptions\SendKuyException;
use SendKuy\SendKuy;

final class ClientFactory
{
    /**
     * @param  array<array-key, mixed>  $config
     */
    public static function make(#[\SensitiveParameter] array $config, ?ClientInterface $http = null): SendKuy
    {
        $key = $config['api_key'] ?? null;

        if (! is_string($key) || trim($key) === '') {
            throw new SendKuyException('Set SENDKUY_API_KEY in your .env to use the SendKuy client.');
        }

        $base = $config['base_url'] ?? null;
        $timeout = $config['timeout'] ?? null;
        $retries = $config['max_retries'] ?? null;

        $options = [
            'base_url' => is_string($base) && trim($base) !== '' ? $base : SendKuy::DEFAULT_BASE_URL,
            'timeout' => is_numeric($timeout) ? (float) $timeout : 30.0,
            'max_retries' => is_numeric($retries) ? (int) $retries : 2,
        ];

        if ($http !== null) {
            $options['http_client'] = $http;
        }

        return new SendKuy($key, $options);
    }
}
