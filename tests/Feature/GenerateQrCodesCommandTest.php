<?php

declare(strict_types=1);

namespace Lesekloden\Qr\Tests\Feature;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Lesekloden\Qr\QrVersionResolver;
use Lesekloden\Qr\Support\UniformVersionMatrixFactory;
use Lesekloden\Qr\Tests\TestCase;

class GenerateQrCodesCommandTest extends TestCase
{
    public function test_it_publishes_the_configuration_file(): void
    {
        $publishedConfigPath = config_path('qr.php');

        if (File::exists($publishedConfigPath)) {
            File::delete($publishedConfigPath);
        }

        Artisan::call('vendor:publish', [
            '--provider' => 'Lesekloden\Qr\QrServiceProvider',
            '--tag' => 'lesekloden-qr-config',
        ]);

        $this->assertFileExists($publishedConfigPath);
    }

    public function test_it_generates_qr_codes_for_configured_urls(): void
    {
        $outputRoot = storage_path('framework/testing/disks/public');

        File::ensureDirectoryExists($outputRoot);
        File::cleanDirectory($outputRoot);

        Config::set('filesystems.disks.public.root', $outputRoot);
        Config::set('qr.paths', [
            'https://mypage.com/about',
            'https://mypage.com/store/',
            'https://mypage.com/',
            'https://mypage.com/store/books',
        ]);

        $this->artisan('qr:generate')
            ->expectsOutputToContain('Generated [qr/about.png]')
            ->expectsOutputToContain('Generated [qr/store.png]')
            ->expectsOutputToContain('Generated [qr/home.png]')
            ->expectsOutputToContain('Generated [qr/store-books.png]')
            ->assertExitCode(0);

        $this->assertFileExists($outputRoot.'/qr/about.png');
        $this->assertFileExists($outputRoot.'/qr/store.png');
        $this->assertFileExists($outputRoot.'/qr/home.png');
        $this->assertFileExists($outputRoot.'/qr/store-books.png');
    }

    public function test_it_embeds_a_logo_when_a_valid_logo_path_is_configured(): void
    {
        $outputRoot = storage_path('framework/testing/disks/public');
        $logoPath = storage_path('framework/testing/logo.png');

        $this->createLogo($logoPath);

        Config::set('filesystems.disks.public.root', $outputRoot);
        Config::set('qr.paths', ['https://mypage.com/about']);
        Config::set('qr.logo', $logoPath);
        Config::set('qr.logo_width', 75);

        $this->artisan('qr:generate')->assertExitCode(0);

        $this->assertFileExists($outputRoot.'/qr/about.png');
        $this->assertNotEmpty(File::get($outputRoot.'/qr/about.png'));
    }

    public function test_it_fails_when_the_configured_logo_cannot_be_read(): void
    {
        Config::set('qr.paths', ['https://mypage.com/about']);
        Config::set('qr.logo', storage_path('framework/testing/missing-logo.png'));

        $this->artisan('qr:generate')
            ->expectsOutputToContain('could not be read')
            ->assertExitCode(1);
    }

    public function test_later_duplicate_filenames_overwrite_earlier_files(): void
    {
        $outputRoot = storage_path('framework/testing/disks/public');

        File::ensureDirectoryExists($outputRoot);
        File::cleanDirectory($outputRoot);

        Config::set('filesystems.disks.public.root', $outputRoot);
        Config::set('qr.paths', ['https://mypage.com/about?version=first']);

        $this->artisan('qr:generate')->assertExitCode(0);

        $firstContents = File::get($outputRoot.'/qr/about.png');

        Config::set('qr.paths', [
            'https://mypage.com/about?version=first',
            'https://mypage.com/about?version=second',
        ]);

        $this->artisan('qr:generate')->assertExitCode(0);

        $secondContents = File::get($outputRoot.'/qr/about.png');

        $this->assertNotSame($firstContents, $secondContents);
    }

    public function test_it_uses_the_configured_logo_width(): void
    {
        $outputRoot = storage_path('framework/testing/disks/public');
        $logoPath = storage_path('framework/testing/logo-width.png');

        File::ensureDirectoryExists($outputRoot);
        File::cleanDirectory($outputRoot);
        $this->createLogo($logoPath);

        Config::set('filesystems.disks.public.root', $outputRoot);
        Config::set('qr.paths', ['https://mypage.com/about']);
        Config::set('qr.logo', $logoPath);
        Config::set('qr.logo_width', 40);

        $this->artisan('qr:generate')->assertExitCode(0);

        $smallLogoQrCode = File::get($outputRoot.'/qr/about.png');

        Config::set('qr.logo_width', 90);

        $this->artisan('qr:generate')->assertExitCode(0);

        $largeLogoQrCode = File::get($outputRoot.'/qr/about.png');

        $this->assertNotSame($smallLogoQrCode, $largeLogoQrCode);
    }

    public function test_it_uses_a_uniform_matrix_for_all_paths_by_default(): void
    {
        Config::set('qr.uniform_matrix', true);

        $shortUrl = 'https://mypage.com/fag';
        $longUrl = 'https://mypage.com/fag/norsk-bokmaal';

        $resolver = app(QrVersionResolver::class);
        $forcedVersion = $resolver->resolve([$shortUrl, $longUrl]);

        $this->assertNotNull($forcedVersion);

        $factory = app(UniformVersionMatrixFactory::class);
        $shortMatrix = $factory->create($this->makeQrCode($shortUrl), $forcedVersion);
        $longMatrix = $factory->create($this->makeQrCode($longUrl), $forcedVersion);

        $this->assertSame($shortMatrix->getBlockCount(), $longMatrix->getBlockCount());
    }

    public function test_it_uses_the_configured_output_size(): void
    {
        $outputRoot = storage_path('framework/testing/disks/public');

        File::ensureDirectoryExists($outputRoot);
        File::cleanDirectory($outputRoot);

        Config::set('filesystems.disks.public.root', $outputRoot);
        Config::set('qr.paths', ['https://mypage.com/about']);
        Config::set('qr.size', 360);

        $this->artisan('qr:generate')->assertExitCode(0);

        [$width, $height] = getimagesize($outputRoot.'/qr/about.png');

        $this->assertSame(360, $width);
        $this->assertSame(360, $height);
    }

    private function createLogo(string $path): void
    {
        File::ensureDirectoryExists(dirname($path));

        $image = imagecreatetruecolor(32, 32);
        $background = imagecolorallocate($image, 255, 255, 255);
        $accent = imagecolorallocate($image, 17, 24, 39);

        imagefill($image, 0, 0, $background);
        imagefilledellipse($image, 16, 16, 20, 20, $accent);
        imagepng($image, $path);
    }

    private function makeQrCode(string $url): QrCode
    {
        return new QrCode(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );
    }
}
