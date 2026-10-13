<?php

declare(strict_types=1);

namespace Lesekloden\Qr;

class Qr
{
    public function __construct(private readonly QrCodeGenerator $generator) {}

    public function generateAll(): QrCodeGenerationResult
    {
        return $this->generator->generateAll();
    }
}
