<?php

namespace SendKuy\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SendKuy\Laravel\SendKuyServiceProvider;
use SendKuy\Laravel\Tests\Support\FakeClient;
use SendKuy\SendKuy;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SendKuyServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('sendkuy.api_key', 'sk_test_laravel');
        $app['config']->set('sendkuy.webhook.secret', 'whsec_laravel_test_secret');
    }

    protected function fakeSendKuy(): FakeClient
    {
        $http = new FakeClient;
        $this->app->instance(SendKuy::class, new SendKuy('sk_test_laravel', [
            'http_client' => $http,
            'sleep' => static function (float $seconds): void {},
        ]));

        return $http;
    }
}
