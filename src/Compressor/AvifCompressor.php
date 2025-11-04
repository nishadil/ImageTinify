<?php

namespace Nishadil\ImageTinify\Compressor;

use Nishadil\ImageTinify\Utils\FileHelper;
use Nishadil\ImageTinify\Utils\MetadataStripper;
use Nishadil\ImageTinify\Exceptions\ImageTinifyException;
use Symfony\Component\Process\Process;

/**
 * AVIF Compressor
 *
 * Uses cavif (preferred), avifenc, or ImageMagick to compress AVIF images.
 * Supports modes: lossy, lossless, smart
 */
class AvifCompressor extends BaseCompressor
{
    protected function doCompress(string $input, string $output): bool
    {
        $quality = (int)$this->option('quality', 75);
        $mode = $this->option('mode', 'smart');

        // Detect available encoder: cavif or avifenc (libavif)
        $encoder = $this->which('cavif') ?: $this->which('avifenc');

        if ($encoder === null) {
            // Fallback to GD if extension available (PHP 8.1+)
            if (function_exists('imagecreatefromavif') && function_exists('imageavif')) {
                $img = @imagecreatefromavif($input);
                if ($img === false) {
                    throw new ImageTinifyException('Failed to read AVIF image.');
                }
                // quality in GD: 0-100
                imageavif($img, $output, $quality);
                imagedestroy($img);
                return file_exists($output);
            }
            throw new ImageTinifyException('No AVIF encoder found (cavif or avifenc).');
        }

        // Mode-specific flags
        $args = [];
        switch ($mode) {
            case 'lossless':
                // libavif / cavif lossless
                if (str_contains($encoder, 'cavif')) {
                    $args = ['--quality', '100', '--speed', '2', '--depth', '8'];
                } else {
                    $args = ['--lossless', '--speed', '2'];
                }
                break;

            case 'smart':
                // visually lossless, tuned for perceptual quality (TinyPNG style)
                if (str_contains($encoder, 'cavif')) {
                    $args = ['--quality', (string)$quality, '--speed', '4', '--depth', '8'];
                } else {
                    $args = ['--min', (string)max(20, $quality - 10), '--max', (string)$quality, '--speed', '4'];
                }
                break;

            default: // lossy
                if (str_contains($encoder, 'cavif')) {
                    $args = ['--quality', (string)$quality, '--speed', '8', '--depth', '8'];
                } else {
                    $args = ['--min', (string)max(10, $quality - 20), '--max', (string)$quality, '--speed', '8'];
                }
                break;
        }

        // Build command
        $cmd = [$encoder, ...$args, '--overwrite', $input, '-o', $output];
        $proc = new Process($cmd);
        $proc->run();

        if (!$proc->isSuccessful()) {
            throw new ImageTinifyException("AVIF compression failed: " . $proc->getErrorOutput());
        }

        // Strip metadata if not already stripped
        MetadataStripper::strip($output);
        FileHelper::ensureWritableDir($output);

        return file_exists($output);
    }

    /**
     * Finds an executable in PATH.
     */
    protected function which(string $cmd): ?string
    {
        $finder = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $proc = new Process([$finder, $cmd]);
        $proc->run();
        if ($proc->isSuccessful()) {
            $out = trim($proc->getOutput());
            return $out !== '' ? explode(PHP_EOL, $out)[0] : null;
        }
        return null;
    }
}
