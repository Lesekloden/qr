# Readme

This is a thin wrapper around [Endroid QR Code](https://github.com/endroid/qr-code) for creating custom QR codes for the Norwegian learning portal Lesekloden.no.

## Setup

Install the package in a Laravel 12 or 13 project using PHP 8.3 or later with the GD extension enabled. To install directly from GitHub, run:

```bash
composer config repositories.lesekloden-qr vcs https://github.com/Lesekloden/qr.git
composer require lesekloden/lesekloden-qr:dev-main
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
