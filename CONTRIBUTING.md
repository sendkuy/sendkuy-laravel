# Contributing

Thank you for helping improve SendKuy for Laravel.

## Setup

This package builds on [`sendkuy/sdk`](https://github.com/sendkuy/sendkuy-php). Until 1.0.0 is on Packagist, Composer reads it from a sibling folder, so clone both side by side:

```bash
git clone https://github.com/sendkuy/sendkuy-php.git
git clone https://github.com/sendkuy/sendkuy-laravel.git
cd sendkuy-laravel
composer install
composer test
composer analyse
```

## Pull requests

- Keep each pull request to one change, with a test when behaviour changes.
- Use [Conventional Commits](https://www.conventionalcommits.org/) in English, for example `fix(channel): accept an array of recipients`.
- Make sure `composer test`, `composer analyse` and `vendor/bin/pint --test` pass.
