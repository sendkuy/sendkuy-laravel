<?php

namespace SendKuy\Laravel\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

final class Customer extends Model
{
    use Notifiable;

    protected $guarded = [];

    public function routeNotificationForSendKuy(Notification $notification): ?string
    {
        $phone = $this->getAttribute('phone');

        return is_string($phone) ? $phone : null;
    }
}
