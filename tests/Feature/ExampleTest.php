<?php

declare(strict_types=1);

use Lesekloden\Qr\Qr;

it('resolves the singleton', function () {
    expect(app(Qr::class))->toBeInstanceOf(Qr::class);
});

it('returns the same instance from the container', function () {
    expect(app(Qr::class))->toBe(app(Qr::class));
});
