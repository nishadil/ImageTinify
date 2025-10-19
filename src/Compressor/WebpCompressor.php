<?php

namespace Nishadil\ImageTinify\Compressor;

use Nishadil\ImageTinify\Utils\MetadataStripper;
use Symfony\Component\Process\Process;

class WebpCompressor extends BaseCompressor
{
    protected function doCompress(string $input, string $output): bool
    {
        $quality = (int)$this->option('quality', 80);

        // If GD supports webp, use it
        if (function_exists('imagecreatefromwebp')) {
            $img = @imagecreatefromwebp($input);
            if ($img !== false) {
                imagewebp($img, $output, $quality);
                imagedestroy($img);
                MetadataStripper::strip($output);
                return file_exists($output);
            }
        }

        // Try cwebp binary if available
        $cwebp = $this->which('cwebp');
        if ($cwebp !== null) {
            $proc = new Process([$cwebp, '-q', (string)$quality, $input, '-o', $output]);
            $proc->run();
            if ($proc->isSuccessful()) {
                MetadataStripper::strip($output);
                return file_exists($output);
            }
        }

        // Fallback: copy input to output (no-op)
        copy($input, $output);
        return file_exists($output);
    }

    protected function which(string $cmd): ?string
    {
        $proc = new Process([PHP_OS_FAMILY === 'Windows' ? 'where' : 'which', $cmd]);
        $proc->run();
        if ($proc->isSuccessful()) {
            $path = trim($proc->getOutput());
            return $path !== '' ? explode(PHP_EOL, $path)[0] : null;
        }
        return null;
    }
}
