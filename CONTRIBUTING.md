# Contributing

Thanks for your interest in contributing to Flight Signals API!

## Getting Started

1. Fork the repo and clone it locally
2. Run `composer install`
3. Copy `.env.example` to `.env` and run `php artisan key:generate`
4. Install the pre-commit hook: `bash scripts/install-hooks.sh`
5. Start services: `./vendor/bin/sail up -d`

## Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) for code formatting. The pre-commit hook enforces it automatically. You can also run:

```bash
composer fix    # auto-fix
composer lint   # dry-run check
```

## Tests

We use [Pest](https://pestphp.com/) for testing. All new features or bug fixes must include tests.

```bash
composer test
composer test:coverage
```

## Pull Requests

- Keep PRs focused on a single change
- Write descriptive commit messages
- Ensure CI passes before requesting review
