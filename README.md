# Lesekloden QR

This is a thin wrapper around [Endroid QR Code](https://github.com/endroid/qr-code) for creating custom QR codes for the Norwegian learning portal Lesekloden.no.

## Setup

Install the package in a Laravel 12 or 13 project using PHP 8.3 or later with the GD extension enabled:

```bash
composer require lesekloden/lesekloden-qr
php artisan vendor:publish --tag="lesekloden-qr-config"
```

In `config/qr.php`, set `paths` to the URLs, application paths, or named routes to generate codes for:

```php
'paths' => ['https://lesekloden.no/om', '/kaffe', 'route:home'],
'disk' => 'public',
'output_directory' => 'qr',
```

Set `APP_URL` for application paths. Optionally configure the logo and image size, then generate the PNG files:

```bash
php artisan generate:qrs
```

With these storage settings, output is saved under `storage/app/public/qr`.

## Updating an existing installation

If you previously installed directly from GitHub, remove that repository entry and switch to the stable release:

```bash
composer config --unset repositories.lesekloden-qr
composer require lesekloden/lesekloden-qr:^1.0 --with-all-dependencies
```

For subsequent compatible releases:

```bash
composer update lesekloden/lesekloden-qr
```

Review the [changelog](CHANGELOG.md) before updating. To add new settings to an already published config file, compare it with `vendor/lesekloden/lesekloden-qr/config/qr.php` and merge the changes into your application's config.

## Development and releases

Run `composer install` and `composer test` before pushing changes. Use Conventional Commits (or squash-merge PRs with a Conventional Commit title):

- `fix: ...` for a patch release.
- `feat: ...` for a minor release.
- `feat!: ...` or a `BREAKING CHANGE:` footer for a major release.

After the current `main` commit passes CI, release-please prepares or updates a release PR with the next version and changelog. Review and merge that PR to publish the GitHub release and version tag; the Packagist webhook then indexes it automatically. No repeated Packagist submission or manual tag is needed.

Update this README in the same PR whenever installation, requirements, configuration, or usage changes. Documentation-only changes do not normally require a release.

The workflow uses GitHub's built-in token. Its generated release PRs do not trigger additional CI runs; the merged `main` commit must pass the full test suite before a release is created. Package versions come from Git tags, so do not add a `version` field to `composer.json`.
