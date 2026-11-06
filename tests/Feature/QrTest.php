<?php

declare(strict_types=1);

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Lesekloden\Qr\Facades\Qr;
use Lesekloden\Qr\QrCodeGenerator;
use Lesekloden\Qr\QrFilenameResolver;
use Lesekloden\Qr\QrVersionResolver;

beforeEach(function () {
    Storage::fake('public');
});

it('merges defaults and allows host configuration overrides', function () {
    expect(config('qr.size'))->toBe(512)
        ->and(config('qr.logo_width'))->toBe(118)
        ->and(config('qr.logo'))->toBeNull()
        ->and(config('qr.paths'))->toBe([]);

    config(['qr.size' => 640]);
    expect(config('qr.size'))->toBe(640);
});

it('generates PNGs through the facade on the configured disk and directory', function () {
    Storage::fake('qr-output');
    config(['qr.paths' => ['https://example.com/books'], 'qr.disk' => 'qr-output', 'qr.output_directory' => '/codes/']);

    $result = Qr::generateAll();

    expect($result->failures)->toBe([])
        ->and($result->generatedFiles)->toBe([['url' => 'https://example.com/books', 'path' => 'codes/books.png']])
        ->and(Storage::disk('qr-output')->get('codes/books.png'))->toStartWith("\x89PNG\r\n\x1a\n");
    Storage::disk('public')->assertMissing('codes/books.png');
});

it('reports invalid URLs while generating the valid entries', function () {
    config(['qr.paths' => ['not-a-url', null, ' https://example.com/books ']]);

    $this->artisan('generate:qrs')
        ->expectsOutputToContain('Generated: 1. Failed: 2.')
        ->assertFailed();

    Storage::disk('public')->assertExists('qr/books.png');
});

it('handles an empty URL list', function () {
    $this->artisan('generate:qrs')
        ->expectsOutputToContain('Generated: 0. Failed: 0.')
        ->assertSuccessful();
});

it('rejects unsupported output formats', function () {
    config(['qr.format' => 'svg']);
    $this->artisan('generate:qrs')
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

it('resolves application paths and named routes before generating QR codes', function () {
    config(['app.url' => 'https://example.test', 'qr.paths' => ['/about', 'route:books', ['route' => 'book', 'parameters' => ['id' => 7]]]]);
    Route::get('/books', fn () => '')->name('books');
    Route::get('/books/{id}', fn () => '')->name('book');
    Route::getRoutes()->refreshNameLookups();

    $result = Qr::generateAll();
    expect($result->failures)->toBe([])
        ->and(array_column($result->generatedFiles, 'url'))->toBe(['https://example.test/about', route('books'), route('book', ['id' => 7])]);
    Storage::disk('public')->assertExists(['qr/about.png', 'qr/books.png', 'qr/books-7.png']);
});

it('reports missing named routes as generation failures', function () {
    config(['qr.paths' => ['route:missing-route']]);
    $this->artisan('generate:qrs')->expectsOutputToContain('Generated: 0. Failed: 1.')->assertFailed();
});

it('preserves absolute Windows logo paths when reporting unreadable files', function (string $path) {
    config(['qr.logo' => $path]);

    expect(fn () => app(QrCodeGenerator::class)->generateAll())
        ->toThrow(RuntimeException::class, "The logo file [{$path}] could not be read.");
})->with([
    'drive with backslashes' => ['Z:\\lesekloden-missing\\logo.png'],
    'drive with slashes' => ['Z:/lesekloden-missing/logo.png'],
    'network share' => ['\\\\lesekloden-missing\\share\\logo.png'],
]);
