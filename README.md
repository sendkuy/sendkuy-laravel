<p align="center">
  <a href="https://sendkuy.com/?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-laravel">
    <img src="https://raw.githubusercontent.com/sendkuy/sendkuy-laravel/main/art/banner.png" alt="SendKuy for Laravel" width="100%">
  </a>
</p>

# SendKuy for Laravel

WhatsApp, SMS and email notifications for Laravel, a facade for the whole SendKuy API, and signed webhooks that arrive as Laravel events.

[![Latest version](https://img.shields.io/packagist/v/sendkuy/laravel)](https://packagist.org/packages/sendkuy/laravel)
[![Downloads](https://img.shields.io/packagist/dt/sendkuy/laravel)](https://packagist.org/packages/sendkuy/laravel)
[![CI](https://github.com/sendkuy/sendkuy-laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/sendkuy/sendkuy-laravel/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

> **Status:** not released yet. The first release, 1.0.0, is on its way. Star or watch this repository to hear when it lands.

[Baca dalam Bahasa Indonesia](README.id.md)

## Install

```bash
composer require sendkuy/laravel
```

Laravel 12 or 13, PHP 8.2 or newer. Add your key to `.env`:

```env
SENDKUY_API_KEY=sk_live_...
```

## Send from anywhere

```php
use SendKuy\Laravel\Facades\SendKuy;

$messages = SendKuy::messages()->send([
    'channel' => 'whatsapp',
    'to' => ['+6281234567890'],
    'body' => 'Your order is on its way.',
]);
```

Prefer injection? Type-hint `SendKuy\SendKuy` and Laravel hands you the same configured client. Every endpoint of the API has a method; see the [PHP SDK](https://github.com/sendkuy/sendkuy-php) for the full list, errors and pagination.

## Notifications

```php
use Illuminate\Notifications\Notification;
use SendKuy\Laravel\Notifications\SendKuyMessage;

class OrderShipped extends Notification
{
    public function via(object $notifiable): array
    {
        return ['sendkuy'];
    }

    public function toSendKuy(object $notifiable): SendKuyMessage
    {
        return SendKuyMessage::whatsapp('Order #1042 is on its way.')
            ->media('https://example.com/receipts/1042.pdf', 'document');
    }
}
```

Tell the channel where to send by adding a method to your notifiable model:

```php
public function routeNotificationForSendKuy(Notification $notification): string
{
    return $this->phone; // +6281234567890
}
```

Or send without a model:

```php
Notification::route('sendkuy', '+6281234567890')->notify(new OrderShipped);
```

| Builder | Sets |
| --- | --- |
| `SendKuyMessage::whatsapp($body)`, `::sms($body)`, `::email($subject, $body)` | the channel and the text |
| `->template($id, $parameters)` | a WhatsApp template |
| `->media($url, $type, $filename)` | one WhatsApp attachment: `image`, `video`, `audio` or `document` |
| `->attach($url, $filename)` | an email attachment, up to ten |
| `->gateway($id)` | the exact sender to use |
| `->scheduleAt($dateTime)` | send later |

### A retried queued notification is never sent twice

When a notification implements `ShouldQueue`, Laravel retries its job if a worker dies or the job times out. The channel derives the idempotency key from Laravel's notification id, which stays the same across every retry of that job, so SendKuy answers a retry with the first result instead of sending the message again.

Keep `toSendKuy()` deterministic for this to hold: build the message from the notification's own data, not from `now()` or other values that change between runs. A retry that produces a different payload under the same key is refused with `409 idempotency_key_reused`, so the job fails instead of sending twice. To schedule, store the time on the notification when you create it and pass that to `scheduleAt()`.

The channel returns the created messages, so a `NotificationSent` listener can store their ids from `$event->response`.

## Webhooks

The package registers `POST /sendkuy/webhook`, outside the `web` middleware group so CSRF does not apply. Point a webhook at it in your SendKuy dashboard and store the secret:

```env
SENDKUY_WEBHOOK_SECRET=whsec_...
```

Every delivery with a valid signature becomes a `SendKuy\Laravel\Events\WebhookReceived` event; anything else is refused with `403`. If `SENDKUY_WEBHOOK_SECRET` is not set, the route answers `500` and logs an error, so the problem shows up in your SendKuy delivery history instead of passing silently.

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use SendKuy\Laravel\Events\WebhookReceived;

class HandleSendKuyWebhook implements ShouldQueue
{
    public function handle(WebhookReceived $event): void
    {
        if ($event->type === 'message.received') {
            // $event->data is the message
        }
    }
}
```

`$event->id` stays the same across SendKuy's retries of one delivery: use it to skip duplicates. Queue the listener so the route answers within SendKuy's 10-second timeout. New event types reach your listener without a package update.

## Configuration

```bash
php artisan vendor:publish --tag=sendkuy-config
```

| Key | Env | Default |
| --- | --- | --- |
| `api_key` | `SENDKUY_API_KEY` | none |
| `base_url` | `SENDKUY_BASE_URL` | `https://app.sendkuy.com/api/v1` |
| `timeout` | `SENDKUY_TIMEOUT` | `30` seconds |
| `max_retries` | `SENDKUY_MAX_RETRIES` | `2` |
| `webhook.secret` | `SENDKUY_WEBHOOK_SECRET` | none |
| `webhook.path` | `SENDKUY_WEBHOOK_PATH` | `sendkuy/webhook`; set it to an empty value to turn the route off |
| `webhook.tolerance` | `SENDKUY_WEBHOOK_TOLERANCE` | `300` seconds |

## Testing

`Notification::fake()` works as usual. To fake the client itself, bind your own instance:

```php
$this->app->instance(\SendKuy\SendKuy::class, new \SendKuy\SendKuy('sk_test_123', ['http_client' => $fakeClient]));
```

## Links

- [Laravel guide](https://docs.sendkuy.com/laravel?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-laravel)
- [Documentation](https://docs.sendkuy.com/?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-laravel)
- [SendKuy](https://sendkuy.com/?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-laravel)
- [Changelog](CHANGELOG.md)

## License

MIT. See [LICENSE](LICENSE).
