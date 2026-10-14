<?php

namespace SendKuy\Laravel\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use SendKuy\Laravel\ClientFactory;
use SendKuy\Laravel\Tests\Support\FakeClient;

final class ClientFactoryTest extends PHPUnitTestCase
{
    public function test_an_empty_base_url_in_env_falls_back_to_the_default(): void
    {
        $http = new FakeClient([FakeClient::json(200, ['data' => ['object' => 'account', 'id' => 'usr_1']])]);

        ClientFactory::make(['api_key' => 'sk_test_laravel', 'base_url' => '', 'timeout' => '30', 'max_retries' => '2'], $http)
            ->account()
            ->get();

        $this->assertSame('https://app.sendkuy.com/api/v1/account', (string) $http->requests[0]->getUri());
    }
}
