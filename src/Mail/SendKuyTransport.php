<?php

namespace SendKuy\Laravel\Mail;

use Illuminate\Support\Facades\Log;
use SendKuy\Exceptions\SendKuyException;
use SendKuy\Objects\Message;
use SendKuy\SendKuy;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Message as MimeMessage;
use Symfony\Component\Mime\MessageConverter;

final class SendKuyTransport extends AbstractTransport
{
    public const IDEMPOTENCY_HEADER = 'X-SendKuy-Idempotency-Key';

    public const MAX_RECIPIENTS = 100;

    private const FORWARDED_HEADERS = ['List-Unsubscribe', 'List-Unsubscribe-Post'];

    public function __construct(private readonly SendKuy $client, private readonly ?string $gatewayId = null)
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'sendkuy';
    }

    protected function doSend(SentMessage $message): void
    {
        $original = $message->getOriginalMessage();

        if (! $original instanceof MimeMessage) {
            throw new TransportException('SendKuy can only send a composed email, not a raw MIME message.');
        }

        $email = MessageConverter::toEmail($original);
        $params = $this->params($email, $message->getEnvelope()->getRecipients());
        $key = self::text($email->getHeaders(), self::IDEMPOTENCY_HEADER) ?? $message->getMessageId();

        try {
            $sent = $this->client->messages()->send($params, ['idempotency_key' => $key]);
        } catch (SendKuyException $e) {
            if ($e->getErrorCode() === 'idempotency_key_reused') {
                Log::info('SendKuy already sent this email under its idempotency key.', ['idempotency_key' => $key]);

                return;
            }

            throw new TransportException('SendKuy refused the email: '.($e->getErrorCode() ?? 'error').': '.$e->getMessage(), $e->getHttpStatus(), $e);
        }

        foreach ($sent as $row) {
            if ($row->status === 'failed') {
                throw new TransportException('SendKuy could not send the email to '.($row->to ?? 'the recipient').' ('.$row->id.'): '.($row->error ?? 'the message failed').'.');
            }
        }

        $first = $sent[0] ?? null;

        if ($first instanceof Message) {
            $message->setMessageId($first->id);
        }
    }

    /**
     * @param  array<Address>  $envelopeRecipients
     * @return array<string, mixed>
     */
    private function params(Email $email, array $envelopeRecipients): array
    {
        if ($email->getCc() !== [] || $email->getBcc() !== []) {
            throw new TransportException('SendKuy does not send copies: remove Cc and Bcc, or send each recipient their own email.');
        }

        if ($email->getAttachments() !== []) {
            throw new TransportException('SendKuy does not send attached or embedded files from the mailer. Link to the file instead; the SendKuy API takes attachments as URLs, not file contents.');
        }

        $to = array_values(array_unique(array_map(static fn (Address $address): string => $address->getAddress(), $email->getTo())));

        if ($to === []) {
            throw new TransportException('SendKuy needs at least one To recipient.');
        }

        if (count($to) > self::MAX_RECIPIENTS) {
            throw new TransportException('SendKuy sends one email to at most '.self::MAX_RECIPIENTS.' To recipients.');
        }

        foreach ($envelopeRecipients as $recipient) {
            if (! in_array($recipient->getAddress(), $to, true)) {
                throw new TransportException('SendKuy only sends to the To recipients; '.$recipient->getAddress().' is not one of them.');
            }
        }

        $subject = trim((string) $email->getSubject());

        if ($subject === '') {
            throw new TransportException('SendKuy needs a subject for every email.');
        }

        $from = $email->getFrom()[0] ?? null;
        $replyTo = $email->getReplyTo()[0] ?? $from;
        $headers = [];

        foreach (self::FORWARDED_HEADERS as $name) {
            $value = self::text($email->getHeaders(), $name);

            if ($value !== null) {
                $headers[$name] = $value;
            }
        }

        return array_filter([
            'channel' => 'email',
            'to' => $to,
            'subject' => $subject,
            'body' => self::body($email),
            'from_name' => $from?->getName() !== '' ? $from?->getName() : null,
            'reply_to' => $replyTo?->getAddress(),
            'headers' => $headers === [] ? null : $headers,
            'transactional' => true,
            'gateway_id' => $this->gatewayId,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private static function body(Email $email): string
    {
        $html = self::stringify($email->getHtmlBody());

        if ($html !== null && $html !== '') {
            return $html;
        }

        return nl2br(htmlspecialchars((string) self::stringify($email->getTextBody()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    /** @param resource|string|null $body */
    private static function stringify(mixed $body): ?string
    {
        if (is_resource($body)) {
            $content = stream_get_contents($body);

            return $content === false ? null : $content;
        }

        return is_string($body) ? $body : null;
    }

    private static function text(Headers $headers, string $name): ?string
    {
        $header = $headers->get($name);
        $value = $header?->getBody();

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
