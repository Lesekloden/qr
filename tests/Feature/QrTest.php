<?php

declare(strict_types=1);

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Lesekloden\Qr\Facades\Qr;
use Lesekloden\Qr\QrCodeGenerator;
use Lesekloden\Qr\QrFilenameResolver;
use Lesekloden\Qr\QrVersionResolver;

beforeEach(function () {
    Storage::fake('public');
});

it('merges defaults and allows host configuration overrides', function () {
    expect(config('qr.size'))->toBe(320)
        ->and(config('qr.logo'))->toBeNull()
        ->and(config('qr.paths'))->toBe([]);

    config(['qr.size' => 512]);
    expect(config('qr.size'))->toBe(512);
});

it('generates PNGs through the facade on the configured disk and directory', function () {
    Storage::fake('qr-output');
    config(['qr.paths' => ['https://example.com/books'], 'qr.disk' => 'qr-output', 'qr.directory' => '/codes/']);

    $result = Qr::generateAll();

    expect($result->failures)->toBe([])
        ->and($result->generatedFiles)->toBe([['url' => 'https://example.com/books', 'path' => 'codes/books.png']])
        ->and(Storage::disk('qr-output')->get('codes/books.png'))->toStartWith("\x89PNG\r\n\x1a\n");
    Storage::disk('public')->assertMissing('codes/books.png');
});

it('reports invalid URLs while generating the valid entries', function () {
    config(['qr.paths' => ['not-a-url', null, ' https://example.com/books ']]);

    $this->artisan('qr:generate')
        ->expectsOutputToContain('Generated: 1. Failed: 2.')
        ->assertFailed();

    Storage::disk('public')->assertExists('qr/books.png');
});

it('handles an empty URL list', function () {
    $this->artisan('qr:generate')
        ->expectsOutputToContain('Generated: 0. Failed: 0.')
        ->assertSuccessful();
});

it('rejects unsupported output formats', function () {
    config(['qr.format' => 'svg']);
    $this->artisan('qr:generate')
        ->expectsOutputToContain('Only the png format is supported.')
        ->assertFailed();
});

it('reports failed filesystem writes instead of claiming success', function () {
    config(['qr.paths' => ['https://example.com/books']]);
    Storage::shouldReceive('disk')->with('public')->once()->andReturn($disk = Mockery::mock(Filesystem::class));
    $disk->shouldReceive('put')->once()->andReturn(false);

    $result = app(QrCodeGenerator::class)->generateAll();
    expect($result->generatedFiles)->toBe([])
        ->and($result->failures[0]['message'])->toContain('could not be written');
});

it('can disable uniform matrix sizing', function () {
    config(['qr.uniform_matrix' => false]);
    expect(app(QrVersionResolver::class)->resolve(['https://example.com/books']))->toBeNull();
});

it('uses path-based sanitized filenames', function (string $url, string $filename) {
    expect(app(QrFilenameResolver::class)->resolve($url))->toBe($filename);
})->with([
    ['https://example.com', 'home'],
    ['https://example.com/', 'home'],
    ['https://example.com/Books//New-Releases/?edition=2', 'books-new-releases'],
    ['https://example.com/!!!', 'home'],
]);
