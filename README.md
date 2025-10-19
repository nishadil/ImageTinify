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

## Tools to install (strongly recommended)
```bash
sudo apt update
sudo apt install -y pngquant zopfli jpegoptim libjpeg-turbo-progs
```

Confirm installed:
```bash
which pngquant zopfli cjpeg jpegoptim cwebp || true
```

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

## CLI
```bash
php bin/imagetinify input.png output.png --mode=lossy --quality=80
```