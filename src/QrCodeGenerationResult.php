<?php

declare(strict_types=1);

namespace Lesekloden\Qr;

final class QrCodeGenerationResult
{
    /**
     * @param  array<int, array{url: string, path: string}>  $generatedFiles
     * @param  array<int, array{url: string, message: string}>  $failures
     */
    public function __construct(
        public readonly array $generatedFiles,
        public readonly array $failures,
    ) {}
}
