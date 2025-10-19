<?php

namespace Nishadil\ImageTinify\Compressor;

use Nishadil\ImageTinify\Utils\MetadataStripper;
use Nishadil\ImageTinify\Exceptions\ImageTinifyException;
use Symfony\Component\Process\Process;

class JpegCompressor extends BaseCompressor
{
    protected function doCompress(string $input, string $output): bool
    {
        $quality = (int)$this->option('quality', 80);
        $mode = $this->option('mode', 'lossy');

        // Prefer mozjpeg (cjpeg) if available
        $cjpeg = $this->which('cjpeg') ?: $this->which('mozjpeg') ?: $this->which('jpegtran');

        if ($cjpeg !== null && $mode === 'lossy') {
            // Try cjpeg-style invocation; many systems will have mozjpeg's cjpeg
            $tmp = $this->tempFile($input, 'cjpeg') ;
            $cmd = [$cjpeg, '-quality', (string)$quality, '-progressive', '-optimize', '-outfile', $tmp, $input];
            $proc = new Process($cmd);
            $proc->run();
            if (!$proc->isSuccessful()) {
                // fallback: use GD
                copy($input, $tmp);
            }
            // Strip metadata using jpegoptim if available
            $jpegoptim = $this->which('jpegoptim');
            if ($jpegoptim !== null) {
                $proc2 = new Process([$jpegoptim, '--strip-all', $tmp]);
                $proc2->run();
            } else {
                MetadataStripper::strip($tmp);
            }
            rename($tmp, $output);
            return file_exists($output);
        }

        // Fallback to GD
        if (!extension_loaded('gd')) {
            throw new ImageTinifyException('GD extension not loaded and no external jpeg optimizer found.');
        }

        $img = @imagecreatefromjpeg($input);
        if ($img === false) {
            throw new ImageTinifyException('Failed to read JPEG with GD.');
        }

        imagejpeg($img, $output, $quality);
        imagedestroy($img);

        // Attempt jpegoptim if present to strip metadata
        $jpegoptim = $this->which('jpegoptim');
        if ($jpegoptim !== null) {
            $proc = new Process([$jpegoptim, '--strip-all', $output]);
            $proc->run();
        } else {
            MetadataStripper::strip($output);
        }

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

    protected function tempFile(string $src, string $suffix): string
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid("itf_{$suffix}_") . '.' . pathinfo($src, PATHINFO_EXTENSION);
        copy($src, $tmp);
        return $tmp;
    }
}
