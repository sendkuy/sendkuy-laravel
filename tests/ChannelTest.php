<?php

namespace SendKuy\Laravel\Tests;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use SendKuy\Laravel\Exceptions\MissingRecipientException;
use SendKuy\Laravel\Tests\Support\Customer;
use SendKuy\Laravel\Tests\Support\FakeClient;
use SendKuy\Laravel\Tests\Support\OrderShipped;

final class ChannelTest extends TestCase
{
    public function test_a_retried_queued_notification_sends_the_same_payload_and_idempotency_key(): void
    {
        $http = $this->fakeSendKuy()->push(
            FakeClient::json(201, ['data' => [['object' => 'message', 'id' => 'msg_1']]]),
            FakeClient::json(201, ['data' => [['object' => 'message', 'id' => 'msg_1']]]),
        );

        $notification = new OrderShipped;
        $notification->id = '6f1d3c1e-8a52-4c7e-9a43-2f0f4b8f1a10';
        $job = new SendQueuedNotifications(new Customer(['id' => 7, 'phone' => '+6281234567890']), $notification, ['sendkuy']);

        $job->handle($this->app->make(ChannelManager::class));
        $job->handle($this->app->make(ChannelManager::class));

        $this->assertCount(2, $http->requests);
        $key = $http->requests[0]->getHeaderLine('Idempotency-Key');
        $this->assertSame('laravel-notification:6f1d3c1e-8a52-4c7e-9a43-2f0f4b8f1a10:'.Customer::class.':7', $key);
        $this->assertSame($key, $http->requests[1]->getHeaderLine('Idempotency-Key'));
        $this->assertSame([
            'channel' => 'whatsapp',
            'body' => 'Order #1042 is on its way.',
            'media' => ['url' => 'https://example.com/receipts/1042.pdf', 'type' => 'document'],
            'to' => ['+6281234567890'],
        ], $http->body());
    }

    public function test_an_on_demand_notification_keys_on_its_recipient(): void
    {
        $http = $this->fakeSendKuy()->push(
            FakeClient::json(201, ['data' => [['object' => 'message', 'id' => 'msg_1']]]),
            FakeClient::json(201, ['data' => [['object' => 'message', 'id' => 'msg_1']]]),
        );

        $notification = new OrderShipped;
        $notification->id = '0b5f6c1e-2a52-4c7e-9a43-2f0f4b8f1a11';
        $job = new SendQueuedNotifications((new AnonymousNotifiable)->route('sendkuy', '+6281234567890'), $notification, ['sendkuy']);

        $job->handle($this->app->make(ChannelManager::class));
        $job->handle($this->app->make(ChannelManager::class));

        $expected = 'laravel-notification:0b5f6c1e-2a52-4c7e-9a43-2f0f4b8f1a11:'.sha1('+6281234567890');
        $this->assertSame($expected, $http->requests[0]->getHeaderLine('Idempotency-Key'));
        $this->assertSame($expected, $http->requests[1]->getHeaderLine('Idempotency-Key'));
    }

    public function test_a_notifiable_without_a_recipient_is_refused_before_any_call(): void
    {
        $http = $this->fakeSendKuy();

        try {
            (new Customer(['id' => 8]))->notifyNow(new OrderShipped);
            $this->fail('Expected a MissingRecipientException');
        } catch (MissingRecipientException $e) {
            $this->assertStringContainsString(Customer::class, $e->getMessage());
        }

        $this->assertSame([], $http->requests);
    }
}
