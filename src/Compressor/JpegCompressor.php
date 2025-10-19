<?php

namespace Nishadil\ImageTinify\Compressor;

use Nishadil\ImageTinify\Utils\MetadataStripper;
use Nishadil\ImageTinify\Exceptions\ImageTinifyException;
use Symfony\Component\Process\Process;

class JpegCompressor extends BaseCompressor
{
    protected function doCompress(string $input, string $output): bool
    {
        $quality = (int)$this->option('quality', 75); // lower default 75
        $mode = $this->option('mode', 'lossy');

        // Prefer mozjpeg (cjpeg) if available
        // Use flags recommended for best visual/size tradeoff:
        // -quality, -progressive, -optimize, -smooth 0, -baseline/none as needed
        // mozjpeg also supports -sample (set chroma subsampling) but default is fine.
        $cjpeg = $this->which('cjpeg') ?: $this->which('mozjpeg');

        if ($cjpeg !== null && $mode === 'lossy') {
            $tmp = $this->tempFile($input, 'cjpeg');

            // Use mozjpeg-style flags if possible:
            $cmd = [$cjpeg, '-quality', (string)$quality, '-progressive', '-optimize', '-outfile', $tmp, $input];

            // Some mozjpeg builds accept additional flags like -smooth, -trellis, -optimize; keep conservative for compatibility.
            $proc = new Process($cmd);
            $proc->run();

            // if cjpeg failed, fallback to copy and continue to strip/optimize
            if (!$proc->isSuccessful()) {
                copy($input, $tmp);
            }

            // After creating tmp, run jpegoptim if available to further strip and optionally recompress to max quality
            $jpegoptim = $this->which('jpegoptim');
            if ($jpegoptim !== null) {
                // --strip-all removes metadata; --all-progressive ensures progressive; --max=<quality> recompresses if above
                $proc2 = new Process([$jpegoptim, '--strip-all', '--all-progressive', '--max=' . (string)$quality, $tmp]);
                $proc2->run();
            } else {
                // ensure metadata removed by GD fallback stripper
                \Nishadil\ImageTinify\Utils\MetadataStripper::strip($tmp);
            }

            // Move temp to output
            rename($tmp, $output);
            return file_exists($output);
        }

        // Fallback to GD (ensure smaller quality, progressive, and strip metadata)
        if (!extension_loaded('gd')) {
            throw new ImageTinifyException('GD extension not loaded and no external jpeg optimizer found.');
        }

        $img = @imagecreatefromjpeg($input);
        if ($img === false) {
            throw new ImageTinifyException('Failed to read JPEG with GD.');
        }

        // Make progressive (better for web) and write with lower quality
        if (function_exists('imageinterlace')) {
            imageinterlace($img, true);
        }

        imagejpeg($img, $output, $quality);
        imagedestroy($img);

        // Try jpegoptim to strip metadata and re-optimize size
        $jpegoptim = $this->which('jpegoptim');
        if ($jpegoptim !== null) {
            $proc = new Process([$jpegoptim, '--strip-all', '--all-progressive', '--max=' . (string)$quality, $output]);
            $proc->run();
        } else {
            \Nishadil\ImageTinify\Utils\MetadataStripper::strip($output);
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
