<?php

namespace SendKuy\Laravel\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use LogicException;
use SendKuy\Laravel\Exceptions\MissingRecipientException;
use SendKuy\Objects\Message;
use SendKuy\SendKuy;

final class SendKuyChannel
{
    private const KEY_LIMIT = 255;

    public function __construct(private readonly SendKuy $client) {}

    /**
     * @return list<Message>
     */
    public function send(object $notifiable, Notification $notification): array
    {
        if (! method_exists($notification, 'toSendKuy')) {
            throw new LogicException($notification::class.' must define toSendKuy() to be sent through the sendkuy channel.');
        }

        $message = $notification->toSendKuy($notifiable);

        if (! $message instanceof SendKuyMessage) {
            throw new LogicException($notification::class.'::toSendKuy() must return '.SendKuyMessage::class.'.');
        }

        $to = $this->recipients($notifiable, $notification);
        $key = $this->idempotencyKey($notifiable, $notification, $to);

        return $this->client->messages()->send(
            [...$message->toArray(), 'to' => $to],
            $key === null ? [] : ['idempotency_key' => $key],
        );
    }

    /**
     * @return list<string>
     */
    private function recipients(object $notifiable, Notification $notification): array
    {
        $route = method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('sendkuy', $notification)
            : null;

        $to = [];

        foreach (is_array($route) ? $route : [$route] as $value) {
            if (is_string($value) && trim($value) !== '') {
                $to[] = $value;
            }
        }

        if ($to === []) {
            throw MissingRecipientException::for($notifiable);
        }

        return $to;
    }

    /**
     * @param  list<string>  $to
     */
    private function idempotencyKey(object $notifiable, Notification $notification, array $to): ?string
    {
        $id = (string) $notification->id;

        if ($id === '') {
            return null;
        }

        if ($notifiable instanceof Model) {
            $primary = $notifiable->getKey();
            $identity = $notifiable->getMorphClass().':'.(is_scalar($primary) ? (string) $primary : '');
        } else {
            $identity = sha1(implode(',', $to));
        }

        $key = 'laravel-notification:'.$id.':'.$identity;

        return strlen($key) > self::KEY_LIMIT ? 'laravel-notification:'.sha1($key) : $key;
    }
}
