<?php

namespace SendKuy\Laravel\Notifications;

use DateTimeInterface;

final class SendKuyMessage
{
    private ?string $templateId = null;

    /** @var array<string, mixed> */
    private array $templateParameters = [];

    /** @var array{url: string, type: string, filename?: string}|null */
    private ?array $media = null;

    /** @var list<array{url: string, filename?: string}> */
    private array $attachments = [];

    private ?string $gatewayId = null;

    private ?DateTimeInterface $scheduleAt = null;

    private function __construct(
        private readonly string $channel,
        private readonly string $body,
        private readonly ?string $subject = null,
    ) {}

    public static function whatsapp(string $body): self
    {
        return new self('whatsapp', $body);
    }

    public static function sms(string $body): self
    {
        return new self('sms', $body);
    }

    public static function email(string $subject, string $body): self
    {
        return new self('email', $body, $subject);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function template(string $id, array $parameters = []): self
    {
        $this->templateId = $id;
        $this->templateParameters = $parameters;

        return $this;
    }

    public function media(string $url, string $type, ?string $filename = null): self
    {
        $this->media = $filename === null ? ['url' => $url, 'type' => $type] : ['url' => $url, 'type' => $type, 'filename' => $filename];

        return $this;
    }

    public function attach(string $url, ?string $filename = null): self
    {
        $this->attachments[] = $filename === null ? ['url' => $url] : ['url' => $url, 'filename' => $filename];

        return $this;
    }

    public function gateway(string $id): self
    {
        $this->gatewayId = $id;

        return $this;
    }

    public function scheduleAt(DateTimeInterface $at): self
    {
        $this->scheduleAt = $at;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'channel' => $this->channel,
            'body' => $this->body,
            'subject' => $this->subject,
            'template_id' => $this->templateId,
            'template_parameters' => $this->templateParameters === [] ? null : $this->templateParameters,
            'media' => $this->media,
            'attachments' => $this->attachments === [] ? null : $this->attachments,
            'gateway_id' => $this->gatewayId,
            'schedule_at' => $this->scheduleAt,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
