# Changelog

All notable changes to this project are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-11-14

### Added

- The `sendkuy` mail transport: set `MAIL_MAILER=sendkuy` and every Laravel email goes out through SendKuy, with an optional `gateway_id` per mailer. The sender name, `Reply-To` (or the from address when there is none) and `List-Unsubscribe` / `List-Unsubscribe-Post` are passed on, and each email is sent as `transactional`. `Cc`, `Bcc`, envelope-only recipients and files are refused with a `TransportException`, as are failed messages and API errors.
- Notifications sent through the `mail` channel carry an idempotency key built from the notification id and its recipients, so a retried queued notification is never sent twice. `X-SendKuy-Idempotency-Key` sets the key for any other email.

### Changed

- Documentation follows SendKuy API v1 as of this release: a refused send (`4xx`) and a send whose every message failed because of its sender are no longer remembered under their idempotency key, so a retried notification goes through once the cause is fixed.

## [1.1.0] - 2026-10-28

### Changed

- Documentation follows SendKuy API v1 as of this release: `message.received` carries who wrote (`from`), the channel and any media, and its `id` and `conversation_id` are `null` when the account forwards incoming messages to its webhook only; a retried notification after a server error returns the message that already went out instead of sending it again.

## [1.0.0] - 2026-10-14

### Added

- Auto-discovered service provider, config, and a `SendKuy` facade over `sendkuy/sdk`.
- The `sendkuy` notification channel with `SendKuyMessage`, whose idempotency key follows the notification id so a retried queued notification is never sent twice.
- A signed webhook route that dispatches `WebhookReceived`.
