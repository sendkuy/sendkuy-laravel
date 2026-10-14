<?php

namespace SendKuy\Laravel\Tests\Support;

use Illuminate\Notifications\Notification;
use SendKuy\Laravel\Notifications\SendKuyMessage;

final class OrderShipped extends Notification
{
    /**
     * @return list<string>
     */
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
