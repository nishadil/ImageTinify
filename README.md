# Nishadil / ImageTinify

ImageTinify is an open-source PHP image optimization library inspired by TinyPNG/TinyJPG.
It provides a simple API and CLI to compress PNG, JPEG and WebP images using a combination
of native PHP methods and optional best-in-class CLI tools (`pngquant`, `zopfli`, `mozjpeg`, `jpegoptim`).

## Features
- PNG, JPEG, WebP support
- Lossy and lossless modes
- Optional integration with external tools
- Laravel/CMS friendly
- CLI utility

## Installation
```bash
composer require nishadil/imagetinify
```

## Usage

```php
use Nishadil\ImageTinify\ImageTinify;

$tiny = new ImageTinify();
$tiny->compress('uploads/input.png', 'uploads/input-tinified.png', [
    'mode' => 'lossy',
    'quality' => '65-85'
]);

```