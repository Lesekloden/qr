<div align="center">
    <h1>Lesekloden QR</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/lesekloden/lesekloden-qr"><img src="https://img.shields.io/packagist/v/lesekloden/lesekloden-qr.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/lesekloden/lesekloden-qr"><img src="https://img.shields.io/packagist/php-v/lesekloden/lesekloden-qr.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/lesekloden/lesekloden-qr"><img src="https://badge.laravel.cloud/badge/lesekloden/lesekloden-qr?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/lesekloden/lesekloden-qr/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/lesekloden/lesekloden-qr/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/lesekloden/lesekloden-qr"><img src="https://img.shields.io/packagist/dt/lesekloden/lesekloden-qr.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Generate branded PNG QR codes from configured URLs, with optional logos and uniform QR grid density.

## Installation

You can install the package via Composer:

```bash
composer require lesekloden/lesekloden-qr
```

## Usage

PHP 8.3 or later, Laravel 12 or 13, and the GD extension are required.

Publish the configuration:

```bash
php artisan vendor:publish --tag="lesekloden-qr-config"
```

Configure `config/qr.php` in your application:

```php
return [
    'logo' => 'storage/app/public/logo-qr.png', // Optional; use null for no logo.
    'logo_width' => 118,
    'size' => 512,
    'uniform_matrix' => true,
    'paths' => [
        'https://lesekloden.no/om',
        'https://lesekloden.no/fag/norsk-bokmaal',
    ],
    'disk' => 'public',
    'directory' => 'qr',
    'format' => 'png',
];
```

Generate the QR codes:

```bash
php artisan qr:generate
```

The default disk and directory write to `storage/app/public/qr`. Filenames use the URL path: `/` becomes `home.png`, and `/fag/norsk-bokmaal` becomes `fag-norsk-bokmaal.png`. URLs with the same sanitized path overwrite the same file, including URLs that differ only by host or query string.

The command reports each generated file and failure, returning a nonzero exit code when generation fails. Only PNG output is supported. Logos must be readable raster images at an absolute or application-relative path; SVG logos are unsupported.

You can also generate programmatically:

```php
use Lesekloden\Qr\Facades\Qr;

$result = Qr::generateAll();

$result->generatedFiles; // Entries with url and path.
$result->failures;       // Entries with url and message.
```

`Lesekloden\Qr\QrCodeGenerator` can also be resolved from the container. Package defaults have no logo or URLs configured and generate 320×320 images. Uniform matrix sizing selects a shared QR version for all configured URLs; set `uniform_matrix` to `false` to size each grid independently.

## Development

```bash
composer install
composer test
composer serve
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Lesekloden QR! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Simen F. Kristoffersen](https://github.com/lesekloden)
- [All Contributors](../../contributors)

## License

Lesekloden QR is open-sourced software licensed under the [MIT license](LICENSE.md).
