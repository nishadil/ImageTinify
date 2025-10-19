<?php

namespace Nishadil\ImageTinify;

use Nishadil\ImageTinify\Compressor\PngCompressor;
use Nishadil\ImageTinify\Compressor\JpegCompressor;
use Nishadil\ImageTinify\Compressor\WebpCompressor;
use Nishadil\ImageTinify\Exceptions\ImageTinifyException;

class ImageTinify
{
    /**
     * Compress an image file.
     *
     * @param string $input  Path to input file
     * @param string|null $output Path to output file (defaults to input-tinified.ext)
     * @param array $options Options: mode (lossy|lossless), quality (int or string)
     * @return bool
     * @throws ImageTinifyException
     */
    public function compress(string $input, ?string $output = null, array $options = []): bool
    {
        if (!file_exists($input)) {
            throw new ImageTinifyException("Input not found: {$input}");
        }

        $ext = strtolower(pathinfo($input, PATHINFO_EXTENSION));
        $output = $output ?? $this->defaultOutputPath($input);

        switch ($ext) {
            case 'png':
                $compressor = new PngCompressor();
                break;
            case 'jpg':
            case 'jpeg':
                $compressor = new JpegCompressor();
                break;
            case 'webp':
                $compressor = new WebpCompressor();
                break;
            default:
                throw new ImageTinifyException("Unsupported file type: {$ext}");
        }

        return $compressor->compress($input, $output, $options);
    }

    protected function defaultOutputPath(string $input): string
    {
        $info = pathinfo($input);
        return $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . '-tinified.' . $info['extension'];
    }
}
