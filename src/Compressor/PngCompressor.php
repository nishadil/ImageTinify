<?php

namespace Nishadil\ImageTinify\Compressor;

use Nishadil\ImageTinify\Utils\FileHelper;
use Nishadil\ImageTinify\Utils\MetadataStripper;
use Nishadil\ImageTinify\Exceptions\ImageTinifyException;
use Symfony\Component\Process\Process;

class PngCompressor extends BaseCompressor
{
    protected function doCompress(string $input, string $output): bool
    {
        // Prefer pngquant if available
        $quality = $this->option('quality', '65-85');
        $mode = $this->option('mode', 'lossy');

        // Step 1: run pngquant if installed
        $pngquantPath = $this->which('pngquant');
        if ($pngquantPath !== null && $mode === 'lossy') {
            $tmp = $this->tempFile($input, 'pngquant');
            $cmd = [$pngquantPath, '--quality=' . $quality, '--speed', '1', '--output', $tmp, '--force', $input];
            $proc = new Process($cmd);
            $proc->run();
            if (!$proc->isSuccessful()) {
                // fallback to copy
                copy($input, $tmp);
            }
            // Step 2: zopfli if available
            $zopfli = $this->which('zopfli');
            if ($zopfli !== null) {
                $proc2 = new Process([$zopfli, $tmp]);
                $proc2->run();
            }
            // Step 3: strip metadata
            MetadataStripper::strip($tmp);
            rename($tmp, $output);
            return file_exists($output);
        }

        // Fallback: Use GD to re-save with optimized settings
        if (!extension_loaded('gd')) {
            // If no GD and no pngquant, throw
            throw new ImageTinifyException('No pngquant available and GD extension is not loaded.');
        }

        $img = @imagecreatefrompng($input);
        if ($img === false) {
            throw new ImageTinifyException('Failed to read PNG with GD.');
        }

        imagesavealpha($img, true);
        // imagepng quality param: 0 (no compression) - 9
        // use max compression, then optionally run zopfli externally
        imagepng($img, $output, 9);
        imagedestroy($img);

        // run zopfli if available
        $zopfli = $this->which('zopfli');
        if ($zopfli !== null) {
            $proc = new Process([$zopfli, $output]);
            $proc->run();
        }

        MetadataStripper::strip($output);
        return file_exists($output);
    }

    protected function which(string $cmd): ?string
    {
        // cross-platform simple which
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
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid("itf_{$suffix}_") . '.' . FileHelper::guessExtension($src);
        copy($src, $tmp);
        return $tmp;
    }
}
