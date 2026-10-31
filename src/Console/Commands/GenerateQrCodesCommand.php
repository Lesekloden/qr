<?php

declare(strict_types=1);

namespace Lesekloden\Qr\Console\Commands;

use Illuminate\Console\Command;
use Lesekloden\Qr\QrCodeGenerator;
use Throwable;

class GenerateQrCodesCommand extends Command
{
    protected $signature = 'generate:qrs';

    protected $description = 'Generate QR codes for the configured URLs.';

    public function __construct(private readonly QrCodeGenerator $qrCodeGenerator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $result = $this->qrCodeGenerator->generateAll();
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        foreach ($result->generatedFiles as $generatedFile) {
            $this->line(sprintf('Generated [%s] for [%s]', $generatedFile['path'], $generatedFile['url']));
        }

        foreach ($result->failures as $failure) {
            $this->error(sprintf('Failed [%s]: %s', $failure['url'], $failure['message']));
        }

        $this->newLine();
        $this->info(sprintf(
            'QR generation finished. Generated: %d. Failed: %d.',
            count($result->generatedFiles),
            count($result->failures),
        ));

        return $result->failures === [] ? self::SUCCESS : self::FAILURE;
    }
}
