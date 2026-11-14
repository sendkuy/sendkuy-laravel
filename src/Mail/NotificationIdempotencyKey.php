<?php

namespace SendKuy\Laravel\Mail;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Address;

final class NotificationIdempotencyKey
{
    private const PREFIX = 'laravel-mail-notification:';

    private const KEY_LIMIT = 255;

    public function __construct(private readonly Repository $config) {}

    public function handle(MessageSending $event): void
    {
        $id = $event->data['__laravel_notification_id'] ?? null;
        $mailer = $event->data['mailer'] ?? null;
        $headers = $event->message->getHeaders();

        if (! is_string($id) || $id === '' || ! is_string($mailer) || $headers->has(SendKuyTransport::IDEMPOTENCY_HEADER)) {
            return;
        }

        if ($this->config->get("mail.mailers.{$mailer}.transport") !== 'sendkuy') {
            return;
        }

        $to = array_map(static fn (Address $address): string => strtolower($address->getAddress()), $event->message->getTo());
        sort($to);

        $key = self::PREFIX.$id.':'.sha1(implode(',', $to));

        $headers->addTextHeader(SendKuyTransport::IDEMPOTENCY_HEADER, strlen($key) > self::KEY_LIMIT ? self::PREFIX.sha1($key) : $key);
    }
}
