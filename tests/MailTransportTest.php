<?php

namespace SendKuy\Laravel\Tests;

use GuzzleHttp\Psr7\Response;
use Illuminate\Mail\Message;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as Notifications;
use PHPUnit\Framework\Attributes\DataProvider;
use SendKuy\Laravel\Mail\SendKuyTransport;
use SendKuy\Laravel\Tests\Support\FakeClient;
use Symfony\Component\Mailer\Exception\TransportException;

final class MailTransportTest extends TestCase
{
    private const UNSUBSCRIBE = '<https://socialkuy.test/news/unsubscribe/42?signature=abc>';

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('mail.default', 'sendkuy');
        $app['config']->set('mail.mailers.sendkuy', ['transport' => 'sendkuy', 'gateway_id' => 'gw_test']);
        $app['config']->set('mail.from', ['address' => 'hello@socialkuy.test', 'name' => 'SocialKuy']);
    }

    /** @param array<string, mixed> $overrides */
    private static function sent(array $overrides = []): Response
    {
        return FakeClient::json(201, ['object' => 'list', 'data' => [[
            'object' => 'message',
            'id' => 'msg_1',
            'channel' => 'email',
            'status' => 'delivered',
            'to' => 'a@x.test',
            'error' => null,
            ...$overrides,
        ]]]);
    }

    public function test_a_markdown_mail_maps_to_one_api_call(): void
    {
        $http = $this->fakeSendKuy()->push(self::sent());

        Mail::html('<p>Halo</p>', function (Message $message): void {
            $message->to('a@x.test')->subject('Kabar produk')->replyTo('support@socialkuy.test');
            $message->getHeaders()->addTextHeader('List-Unsubscribe', self::UNSUBSCRIBE);
            $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            $message->getHeaders()->addTextHeader('X-Other', 'y');
        });

        $this->assertSame([
            'channel' => 'email',
            'to' => ['a@x.test'],
            'subject' => 'Kabar produk',
            'body' => '<p>Halo</p>',
            'from_name' => 'SocialKuy',
            'reply_to' => 'support@socialkuy.test',
            'headers' => ['List-Unsubscribe' => self::UNSUBSCRIBE, 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click'],
            'transactional' => true,
            'gateway_id' => 'gw_test',
        ], $http->body());
    }

    public function test_without_reply_to_the_from_address_receives_replies(): void
    {
        $http = $this->fakeSendKuy()->push(self::sent());

        Mail::html('<p>Halo</p>', fn (Message $message) => $message->to('a@x.test')->subject('Halo'));

        $this->assertSame('hello@socialkuy.test', $http->body()['reply_to']);
    }

    public function test_a_text_only_mail_is_escaped_with_line_breaks_kept(): void
    {
        $http = $this->fakeSendKuy()->push(self::sent());

        Mail::raw("<b>Halo</b>\nBaris dua", fn (Message $message) => $message->to('a@x.test')->subject('Halo'));

        $this->assertSame("&lt;b&gt;Halo&lt;/b&gt;<br />\nBaris dua", $http->body()['body']);
    }

    /** @return array<string, array{string}> */
    public static function refused(): array
    {
        return ['cc' => ['cc'], 'bcc' => ['bcc'], 'attachment' => ['attachment'], 'embedded image' => ['embedded'], '101 recipients' => ['many']];
    }

    #[DataProvider('refused')]
    public function test_what_the_api_cannot_carry_is_refused(string $case): void
    {
        $http = $this->fakeSendKuy();

        try {
            Mail::html('<p>Halo</p>', function (Message $message) use ($case): void {
                $message->to('a@x.test')->subject('Halo');

                match ($case) {
                    'cc' => $message->cc('b@x.test'),
                    'bcc' => $message->bcc('b@x.test'),
                    'attachment' => $message->attachData('isi', 'nota.txt'),
                    'embedded' => $message->embedData('gambar', 'logo.png', 'image/png'),
                    'many' => $message->to(array_map(static fn (int $i): string => "u{$i}@x.test", range(1, 101))),
                };
            });
            $this->fail('The email was not refused.');
        } catch (TransportException $e) {
            $this->assertNotSame('', $e->getMessage());
        }

        $this->assertSame([], $http->requests);
    }

    public function test_the_idempotency_key_header_wins_and_is_not_forwarded(): void
    {
        $http = $this->fakeSendKuy()->push(self::sent());

        Mail::html('<p>Halo</p>', function (Message $message): void {
            $message->to('a@x.test')->subject('Halo');
            $message->getHeaders()->addTextHeader(SendKuyTransport::IDEMPOTENCY_HEADER, 'k1');
        });

        $this->assertSame('k1', $http->requests[0]->getHeaderLine('Idempotency-Key'));
        $this->assertStringNotContainsString('k1', (string) $http->requests[0]->getBody());
    }

    public function test_a_notification_gets_a_key_per_recipient(): void
    {
        $http = $this->fakeSendKuy()->push(self::sent(), self::sent(), self::sent());
        $notification = new class extends Notification
        {
            /** @return list<string> */
            public function via(object $notifiable): array
            {
                return ['mail'];
            }

            public function toMail(object $notifiable): MailMessage
            {
                return (new MailMessage)->subject('Verifikasi email')->line('Klik tombol di bawah.');
            }
        };
        $notification->id = '6f1d3c1e-8a52-4c7e-9a43-2f0f4b8f1a10';
        $a = (new AnonymousNotifiable)->route('mail', 'a@x.test');
        $b = (new AnonymousNotifiable)->route('mail', 'b@x.test');

        Notifications::send([$a, $b], $notification);
        Notifications::send([$a], $notification);

        $keys = array_map(static fn ($request): string => $request->getHeaderLine('Idempotency-Key'), $http->requests);

        $this->assertCount(3, $keys);
        $this->assertStringStartsWith('laravel-mail-notification:6f1d3c1e-8a52-4c7e-9a43-2f0f4b8f1a10:', $keys[0]);
        $this->assertNotSame($keys[0], $keys[1]);
        $this->assertSame($keys[0], $keys[2]);
    }

    public function test_a_plain_mailable_falls_back_to_the_message_id(): void
    {
        $http = $this->fakeSendKuy()->push(self::sent());

        Mail::html('<p>Halo</p>', fn (Message $message) => $message->to('a@x.test')->subject('Halo'));

        $key = $http->requests[0]->getHeaderLine('Idempotency-Key');
        $this->assertStringContainsString('@', $key);
        $this->assertStringStartsNotWith('laravel-mail-notification:', $key);
    }

    public function test_a_failed_message_and_an_api_error_become_transport_exceptions(): void
    {
        $this->fakeSendKuy()->push(
            self::sent(['status' => 'failed', 'error' => 'mailbox full']),
            FakeClient::json(402, ['error' => ['type' => 'invalid_request_error', 'code' => 'insufficient_credits', 'message' => 'Not enough email credit.']]),
        );

        foreach (['msg_1', 'insufficient_credits'] as $expected) {
            try {
                Mail::html('<p>Halo</p>', fn (Message $message) => $message->to('a@x.test')->subject('Halo'));
                $this->fail('The failure was swallowed.');
            } catch (TransportException $e) {
                $this->assertStringContainsString($expected, $e->getMessage());
            }
        }
    }

    public function test_a_reused_key_counts_as_already_sent_but_one_in_progress_does_not(): void
    {
        $this->fakeSendKuy()->push(
            FakeClient::json(409, ['error' => ['type' => 'invalid_request_error', 'code' => 'idempotency_key_reused', 'message' => 'Already used.']]),
            ...array_fill(0, 3, FakeClient::json(409, ['error' => ['type' => 'invalid_request_error', 'code' => 'idempotency_request_in_progress', 'message' => 'Still running.']])),
        );

        Mail::html('<p>Halo</p>', fn (Message $message) => $message->to('a@x.test')->subject('Halo'));

        $this->expectException(TransportException::class);
        Mail::html('<p>Halo</p>', fn (Message $message) => $message->to('a@x.test')->subject('Halo'));
    }
}
