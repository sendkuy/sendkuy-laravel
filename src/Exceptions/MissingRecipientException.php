<?php

namespace SendKuy\Laravel\Exceptions;

use SendKuy\Exceptions\SendKuyException;

final class MissingRecipientException extends SendKuyException
{
    public static function for(object $notifiable): self
    {
        return new self(sprintf(
            "%s has no SendKuy recipient. Return a phone number or email from routeNotificationForSendKuy(), or send with Notification::route('sendkuy', \$to).",
            $notifiable::class,
        ));
    }
}
