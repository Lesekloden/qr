<?php

declare(strict_types=1);

namespace Lesekloden\Qr\Support;

use Endroid\QrCode\ImageData\LogoImageData;
use Endroid\QrCode\Logo\LogoInterface;
use Endroid\QrCode\QrCodeInterface;
use Endroid\QrCode\RoundBlockSizeMode;
use Exception;
use GdImage;

/**
 * Writes PNG QR codes with an optional shared QR version.
 * This keeps module density and logo space consistent across outputs.
 * It also composites the configured logo onto the generated image.
 */
class UniformPngWriter
{
    public function __construct(private readonly UniformVersionMatrixFactory $matrixFactory) {}

    public function write(QrCodeInterface $qrCode, ?LogoInterface $logo = null, ?int $forcedVersion = null): string
    {
        if (! extension_loaded('gd')) {
            throw new Exception('Unable to generate image: please check if the GD extension is enabled and configured correctly');
        }

        $matrix = $this->matrixFactory->create($qrCode, $forcedVersion);

        $baseBlockSize = $qrCode->getRoundBlockSizeMode() === RoundBlockSizeMode::None ? 10 : (int) $matrix->getBlockSize();
        $baseImageSize = $matrix->getBlockCount() * $baseBlockSize;
        $outerSize = $matrix->getOuterSize();

        if ($baseImageSize < 1 || $outerSize < 1) {
            throw new Exception('The configured QR size is too small for the encoded data.');
        }

        $baseImage = imagecreatetruecolor($baseImageSize, $baseImageSize);

        if (! $baseImage) {
            throw new Exception('Unable to generate image: please check if the GD extension is enabled and configured correctly');
        }

        $foregroundColor = imagecolorallocatealpha(
            $baseImage,
            min(255, max(0, $qrCode->getForegroundColor()->getRed())),
            min(255, max(0, $qrCode->getForegroundColor()->getGreen())),
            min(255, max(0, $qrCode->getForegroundColor()->getBlue())),
            min(127, max(0, $qrCode->getForegroundColor()->getAlpha())),
        );

        if ($foregroundColor === false) {
            throw new Exception('Unable to generate image: please check if the GD extension is enabled and configured correctly');
        }

        $transparentColor = imagecolorallocatealpha($baseImage, 255, 255, 255, 127);

        if ($transparentColor === false) {
            throw new Exception('Unable to generate image: please check if the GD extension is enabled and configured correctly');
        }

        imagefill($baseImage, 0, 0, $transparentColor);

        for ($rowIndex = 0; $rowIndex < $matrix->getBlockCount(); $rowIndex++) {
            for ($columnIndex = 0; $columnIndex < $matrix->getBlockCount(); $columnIndex++) {
                if ($matrix->getBlockValue($rowIndex, $columnIndex) === 1) {
                    imagefilledrectangle(
                        $baseImage,
                        $columnIndex * $baseBlockSize,
                        $rowIndex * $baseBlockSize,
                        ($columnIndex + 1) * $baseBlockSize - 1,
                        ($rowIndex + 1) * $baseBlockSize - 1,
                        $foregroundColor,
                    );
                }
            }
        }

        $targetImage = imagecreatetruecolor($outerSize, $outerSize);

        if (! $targetImage) {
            throw new Exception('Unable to generate image: please check if the GD extension is enabled and configured correctly');
        }

        $backgroundColor = imagecolorallocatealpha(
            $targetImage,
            min(255, max(0, $qrCode->getBackgroundColor()->getRed())),
            min(255, max(0, $qrCode->getBackgroundColor()->getGreen())),
            min(255, max(0, $qrCode->getBackgroundColor()->getBlue())),
            min(127, max(0, $qrCode->getBackgroundColor()->getAlpha())),
        );

        if ($backgroundColor === false) {
            throw new Exception('Unable to generate image: please check if the GD extension is enabled and configured correctly');
        }

        imagefill($targetImage, 0, 0, $backgroundColor);

        imagecopyresampled(
            $targetImage,
            $baseImage,
            $matrix->getMarginLeft(),
            $matrix->getMarginLeft(),
            0,
            0,
            $matrix->getInnerSize(),
            $matrix->getInnerSize(),
            imagesx($baseImage),
            imagesy($baseImage),
        );

        if ($qrCode->getBackgroundColor()->getAlpha() > 0) {
            imagesavealpha($targetImage, true);
        }

        if ($logo instanceof LogoInterface) {
            $this->addLogo($logo, $targetImage, $matrix->getOuterSize());
        }

        ob_start();
        imagepng($targetImage);

        return strval(ob_get_clean());
    }

    private function addLogo(LogoInterface $logo, GdImage $targetImage, int $outerSize): void
    {
        $logoImageData = LogoImageData::createForLogo($logo);

        if ($logoImageData->getMimeType() === 'image/svg+xml') {
            throw new Exception('GD Writer does not support SVG logo');
        }

        if ($logoImageData->getPunchoutBackground()) {
            $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);

            if ($transparent === false) {
                throw new Exception('Unable to allocate color: please check if the GD extension is enabled and configured correctly');
            }

            imagealphablending($targetImage, false);
            $xOffsetStart = (int) ($outerSize / 2 - $logoImageData->getWidth() / 2);
            $yOffsetStart = (int) ($outerSize / 2 - $logoImageData->getHeight() / 2);

            for ($xOffset = $xOffsetStart; $xOffset < $xOffsetStart + $logoImageData->getWidth(); $xOffset++) {
                for ($yOffset = $yOffsetStart; $yOffset < $yOffsetStart + $logoImageData->getHeight(); $yOffset++) {
                    imagesetpixel($targetImage, $xOffset, $yOffset, $transparent);
                }
            }
        }

        imagecopyresampled(
            $targetImage,
            $logoImageData->getImage(),
            (int) ($outerSize / 2 - $logoImageData->getWidth() / 2),
            (int) ($outerSize / 2 - $logoImageData->getHeight() / 2),
            0,
            0,
            $logoImageData->getWidth(),
            $logoImageData->getHeight(),
            imagesx($logoImageData->getImage()),
            imagesy($logoImageData->getImage()),
        );
    }
}
