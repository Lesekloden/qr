<?php

declare(strict_types=1);

namespace Lesekloden\Qr;

use BaconQrCode\Encoder\Encoder;
use Endroid\QrCode\Bacon\ErrorCorrectionLevelConverter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Contracts\Config\Repository;

class QrVersionResolver
{
    public function __construct(private readonly Repository $configuration) {}

    /**
     * @param  array<int, mixed>  $paths
     */
    public function resolve(array $paths): ?int
    {
        if (! $this->configuration->get('qr.uniform_matrix', true)) {
            return null;
        }

        $maximumVersion = null;
        $errorCorrectionLevel = ErrorCorrectionLevelConverter::convertToBaconErrorCorrectionLevel(ErrorCorrectionLevel::High);
        $encoding = (string) new Encoding('UTF-8');

        foreach ($paths as $path) {
            $url = is_string($path) ? trim($path) : '';

            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $version = Encoder::encode($url, $errorCorrectionLevel, $encoding)->getVersion()->getVersionNumber();
            $maximumVersion = $maximumVersion === null ? $version : max($maximumVersion, $version);
        }

        return $maximumVersion;
    }
}
