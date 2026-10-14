<?php

namespace SendKuy\Laravel\Tests;

use Illuminate\Support\Facades\Route;

final class WebhookRouteDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('sendkuy.webhook.path', null);
    }

    public function test_a_null_path_registers_no_route(): void
    {
        $this->assertFalse(Route::has('sendkuy.webhook'));
    }
}
