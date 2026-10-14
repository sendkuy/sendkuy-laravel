<?php

namespace SendKuy\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \SendKuy\Resources\Account account()
 * @method static \SendKuy\Resources\Messages messages()
 * @method static \SendKuy\Resources\Numbers numbers()
 * @method static \SendKuy\Resources\Verifications verifications()
 * @method static \SendKuy\Resources\Jobs jobs()
 * @method static \SendKuy\Resources\Contacts contacts()
 * @method static \SendKuy\Resources\Groups groups()
 * @method static \SendKuy\Resources\Templates templates()
 * @method static \SendKuy\Resources\Gateways gateways()
 * @method static \SendKuy\Resources\Webhooks webhooks()
 * @method static \SendKuy\Resources\Campaigns campaigns()
 * @method static \SendKuy\Resources\Conversations conversations()
 * @method static \SendKuy\Resources\Workflows workflows()
 * @method static \SendKuy\Resources\Executions executions()
 * @method static array<array-key, mixed> request(string $method, string $path, array<string, mixed> $params = [], array{idempotency_key?: string, timeout?: float|int} $options = [])
 */
final class SendKuy extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SendKuy\SendKuy::class;
    }
}
