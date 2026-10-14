# Changelog

All notable changes to this project are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Auto-discovered service provider, config, and a `SendKuy` facade over `sendkuy/sdk`.
- The `sendkuy` notification channel with `SendKuyMessage`, whose idempotency key follows the notification id so a retried queued notification is never sent twice.
- A signed webhook route that dispatches `WebhookReceived`.
