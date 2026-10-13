<?php

declare(strict_types=1);

namespace Lesekloden\Qr\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Lesekloden\Qr\Qr
 */
class Qr extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Lesekloden\Qr\Qr::class;
    }
}
