# Changelog

All notable changes to this project are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-10-28

### Changed

- Documentation follows SendKuy API v1 as of this release: `message.received` carries who wrote (`from`), the channel and any media, and its `id` and `conversation_id` are `null` when the account forwards incoming messages to its webhook only; a retried notification after a server error returns the message that already went out instead of sending it again.

## [1.0.0] - 2026-10-14

### Added

- Auto-discovered service provider, config, and a `SendKuy` facade over `sendkuy/sdk`.
- The `sendkuy` notification channel with `SendKuyMessage`, whose idempotency key follows the notification id so a retried queued notification is never sent twice.
- A signed webhook route that dispatches `WebhookReceived`.
