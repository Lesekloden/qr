<?php

declare(strict_types=1);

namespace Lesekloden\Qr\Support;

use BaconQrCode\Common\Version;
use BaconQrCode\Encoder\Encoder;
use Endroid\QrCode\Bacon\ErrorCorrectionLevelConverter;
use Endroid\QrCode\Matrix\Matrix;
use Endroid\QrCode\Matrix\MatrixInterface;
use Endroid\QrCode\QrCodeInterface;

/**
 * Builds QR matrices with an optional forced QR version.
 * This lets short and long URLs share the same module grid size.
 * The uniform matrix is then rendered by the package PNG writer.
 */
class UniformVersionMatrixFactory
{
    public function create(QrCodeInterface $qrCode, ?int $forcedVersion = null): MatrixInterface
    {
        $baconErrorCorrectionLevel = ErrorCorrectionLevelConverter::convertToBaconErrorCorrectionLevel($qrCode->getErrorCorrectionLevel());
        $version = $forcedVersion !== null ? Version::getVersionForNumber($forcedVersion) : null;
        $baconMatrix = Encoder::encode(
            $qrCode->getData(),
            $baconErrorCorrectionLevel,
            strval($qrCode->getEncoding()),
            $version,
        )->getMatrix();

        $blockValues = [];
        $columnCount = $baconMatrix->getWidth();
        $rowCount = $baconMatrix->getHeight();

        for ($rowIndex = 0; $rowIndex < $rowCount; $rowIndex++) {
            $blockValues[$rowIndex] = [];

            for ($columnIndex = 0; $columnIndex < $columnCount; $columnIndex++) {
                $blockValues[$rowIndex][$columnIndex] = $baconMatrix->get($columnIndex, $rowIndex) === 1 ? 1 : 0;
            }
        }

        return new Matrix($blockValues, $qrCode->getSize(), $qrCode->getMargin(), $qrCode->getRoundBlockSizeMode());
    }
}
