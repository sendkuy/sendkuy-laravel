<?php

namespace SendKuy\Laravel\Events;

final class WebhookReceived
{
    /**
     * @param  array<array-key, mixed>  $data
     * @param  array<array-key, mixed>  $payload
     */
    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly array $data,
        public readonly array $payload,
    ) {}
}
