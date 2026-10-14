<?php

namespace SendKuy\Laravel\Http;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;
use SendKuy\Exceptions\SignatureVerificationException;
use SendKuy\Laravel\Events\WebhookReceived;
use SendKuy\Webhook;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class WebhookController
{
    public function __invoke(Request $request, Repository $config, Dispatcher $events, LoggerInterface $logger): SymfonyResponse
    {
        $secret = $config->get('sendkuy.webhook.secret');

        if (! is_string($secret) || $secret === '') {
            $logger->error('A SendKuy webhook arrived, but SENDKUY_WEBHOOK_SECRET is not set.');

            return new JsonResponse(['error' => 'SENDKUY_WEBHOOK_SECRET is not set'], 500);
        }

        $tolerance = $config->get('sendkuy.webhook.tolerance');

        try {
            $payload = Webhook::verify(
                $request->getContent(),
                (string) $request->headers->get('X-SendKuy-Signature', ''),
                $secret,
                is_numeric($tolerance) ? (int) $tolerance : Webhook::TOLERANCE,
            );
        } catch (SignatureVerificationException) {
            return new Response('', 403);
        }

        $type = $payload['event'] ?? null;
        $data = $payload['data'] ?? null;

        $events->dispatch(new WebhookReceived(
            type: is_string($type) ? $type : (string) $request->headers->get('X-SendKuy-Event', ''),
            id: (string) $request->headers->get('X-SendKuy-Event-Id', ''),
            data: is_array($data) ? $data : [],
            payload: $payload,
        ));

        return new Response('', 200);
    }
}
