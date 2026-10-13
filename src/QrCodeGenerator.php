<?php

declare(strict_types=1);

namespace Lesekloden\Qr;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Lesekloden\Qr\Support\UniformPngWriter;
use RuntimeException;
use Throwable;

class QrCodeGenerator
{
    public function __construct(
        private readonly FilesystemFactory $filesystem,
        private readonly Repository $configuration,
        private readonly QrFilenameResolver $filenameResolver,
        private readonly QrVersionResolver $qrVersionResolver,
        private readonly UniformPngWriter $pngWriter,
    ) {}

    public function generateAll(): QrCodeGenerationResult
    {
        $paths = Arr::wrap($this->configuration->get('qr.paths', []));
        $logoPath = $this->resolveLogoPath($this->configuration->get('qr.logo'));
        $disk = (string) $this->configuration->get('qr.disk', 'public');
        $directory = trim((string) $this->configuration->get('qr.directory', 'qr'), '/');
        $format = strtolower((string) $this->configuration->get('qr.format', 'png'));

        if ($format !== 'png') {
            throw new RuntimeException('Only the png format is supported.');
        }

        $forcedVersion = $this->qrVersionResolver->resolve($paths);
        $generatedFiles = [];
        $failures = [];

        foreach ($paths as $path) {
            $url = is_string($path) ? trim($path) : '';

            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                $failures[] = [
                    'url' => $url,
                    'message' => 'The configured value is not a valid URL.',
                ];

                continue;
            }

            try {
                $filename = $this->filenameResolver->resolve($url).'.png';
                $relativePath = ltrim($directory.'/'.$filename, '/');

                $written = $this->filesystem->disk($disk)->put(
                    $relativePath,
                    $this->generatePngContents($url, $logoPath, $forcedVersion),
                );

                if (! $written) {
                    throw new RuntimeException(sprintf('The QR code [%s] could not be written to disk [%s].', $relativePath, $disk));
                }

                $generatedFiles[] = [
                    'url' => $url,
                    'path' => $relativePath,
                ];
            } catch (Throwable $throwable) {
                $failures[] = [
                    'url' => $url,
                    'message' => $throwable->getMessage(),
                ];
            }
        }

        return new QrCodeGenerationResult($generatedFiles, $failures);
    }

    private function generatePngContents(string $url, ?string $logoPath, ?int $forcedVersion): string
    {
        $logoWidth = (int) $this->configuration->get('qr.logo_width', 75);
        $margin = 10;
        $outerSize = max((int) $this->configuration->get('qr.size', 320), 21);
        $innerSize = max($outerSize - ($margin * 2), 1);

        $qrCode = new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $innerSize,
            margin: $margin,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $logo = $logoPath !== null
            ? new Logo(
                path: $logoPath,
                resizeToWidth: max($logoWidth, 1),
                punchoutBackground: true,
            )
            : null;

        return $this->pngWriter->write($qrCode, $logo, $forcedVersion);
    }

    private function resolveLogoPath(mixed $configuredPath): ?string
    {
        if (! is_string($configuredPath) || trim($configuredPath) === '') {
            return null;
        }

        $logoPath = trim($configuredPath);

        if (! Str::startsWith($logoPath, DIRECTORY_SEPARATOR)) {
            $logoPath = base_path($logoPath);
        }

        if (! is_file($logoPath) || ! is_readable($logoPath)) {
            throw new RuntimeException(sprintf('The logo file [%s] could not be read.', $logoPath));
        }

        return $logoPath;
    }
}
