<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | QR Logo
    |--------------------------------------------------------------------------
    |
    | Absolute or project-relative path to the brand logo that should be
    | embedded in generated QR codes.
    |
    | Example:
    | storage/app/public/qr-logo.png
    |
    */
    'logo' => null,

    /*
    |--------------------------------------------------------------------------
    | QR Logo Width
    |--------------------------------------------------------------------------
    |
    | Width, in pixels, for the embedded logo inside each generated QR code.
    | Increase this if you want the logo to appear slightly larger.
    |
    | Example:
    | 75
    |
    */
    'logo_width' => 75,

    /*
    |--------------------------------------------------------------------------
    | QR Output Size
    |--------------------------------------------------------------------------
    |
    | Final width and height, in pixels, for each generated QR image. The
    | default value of 320 produces 320x320 PNG files.
    |
    */
    'size' => 320,

    /*
    |--------------------------------------------------------------------------
    | Uniform Matrix
    |--------------------------------------------------------------------------
    |
    | When enabled, all QR codes generated in the same run will use the same
    | QR version based on the most demanding configured URL. This keeps the
    | grid density, corner squares, and available logo space visually uniform.
    |
    */
    'uniform_matrix' => true,

    /*
    |--------------------------------------------------------------------------
    | QR Paths
    |--------------------------------------------------------------------------
    |
    | Full URLs that should be converted into QR codes. One PNG file will be
    | generated for each URL.
    |
    | Example:
    | [
    |     'https://lesekloden.no/om',
    |     'https://lesekloden.no/kaffe',
    | ]
    |
    */
    'paths' => [],

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | Files are written through Laravel's filesystem. The default "public" disk
    | stores files in:
    | storage/app/public
    |
    | If you switch to another disk, make sure it is configured in
    | config/filesystems.php.
    |
    */
    'disk' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Storage Directory
    |--------------------------------------------------------------------------
    |
    | Directory within the selected disk where generated QR codes will be
    | stored.
    |
    | Example output path on the public disk:
    | storage/app/public/qr/om.png
    | storage/app/public/qr/kaffe.png
    |
    */
    'directory' => 'qr',

    /*
    |--------------------------------------------------------------------------
    | Output Format
    |--------------------------------------------------------------------------
    |
    | Output image format for generated QR codes. The package currently supports
    | only PNG.
    |
    */
    'format' => 'png',
];
