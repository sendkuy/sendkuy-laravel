<?php

namespace SendKuy\Laravel\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use SendKuy\Laravel\Events\WebhookReceived;
use Symfony\Component\HttpFoundation\Response;

final class WebhookTest extends TestCase
{
    private const SECRET = 'whsec_laravel_test_secret';

    private const BODY = '{"event":"message.received","created_at":"2026-10-14T03:00:00+00:00","data":{"object":"message","id":"msg_1","body":"Halo"}}';

    private static function sign(string $body, string $secret = self::SECRET): string
    {
        $timestamp = time();

        return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }

    /**
     * @return TestResponse<Response>
     */
    private function deliver(string $body, string $signature): TestResponse
    {
        return $this->call('POST', '/sendkuy/webhook', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SENDKUY_SIGNATURE' => $signature,
            'HTTP_X_SENDKUY_EVENT' => 'message.received',
            'HTTP_X_SENDKUY_EVENT_ID' => 'whd_9cBe3A1H',
        ], content: $body);
    }

    public function test_a_signed_delivery_fires_webhook_received(): void
    {
        Event::fake([WebhookReceived::class]);

        $this->deliver(self::BODY, self::sign(self::BODY))->assertOk();

        Event::assertDispatched(WebhookReceived::class, static fn (WebhookReceived $event): bool => $event->type === 'message.received'
            && $event->id === 'whd_9cBe3A1H'
            && $event->data === ['object' => 'message', 'id' => 'msg_1', 'body' => 'Halo']);
    }

    public function test_a_bad_signature_is_refused(): void
    {
        Event::fake([WebhookReceived::class]);

        $this->deliver(self::BODY, self::sign(self::BODY, 'whsec_someone_else'))->assertForbidden();

        Event::assertNotDispatched(WebhookReceived::class);
    }

    public function test_a_missing_secret_answers_500(): void
    {
        config(['sendkuy.webhook.secret' => null]);

        $this->deliver(self::BODY, self::sign(self::BODY))->assertStatus(500);
    }
}
