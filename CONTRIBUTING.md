# Contributing

Thank you for helping improve SendKuy for Laravel.

## Setup

This package builds on [`sendkuy/sdk`](https://github.com/sendkuy/sendkuy-php), which Composer installs from Packagist.

```bash
git clone https://github.com/sendkuy/sendkuy-laravel.git
cd sendkuy-laravel
composer install
composer test
composer analyse
```

To work on both packages at once, point Composer at a local checkout of `sendkuy-php` without committing the change: `composer config repositories.local path ../sendkuy-php`.

## Pull requests

- Keep each pull request to one change, with a test when behaviour changes.
- Use [Conventional Commits](https://www.conventionalcommits.org/) in English, for example `fix(channel): accept an array of recipients`.
- Make sure `composer test`, `composer analyse` and `vendor/bin/pint --test` pass.
