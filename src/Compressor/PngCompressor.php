<?php

namespace Nishadil\ImageTinify\Compressor;

use Nishadil\ImageTinify\Utils\FileHelper;
use Nishadil\ImageTinify\Utils\MetadataStripper;
use Nishadil\ImageTinify\Exceptions\ImageTinifyException;
use Symfony\Component\Process\Process;

/**
 * PNG compressor using pngquant -> zopflipng/optipng/pngcrush fallback pipeline.
 *
 * Strategy:
 * 1. Strip metadata/ICC (fixes libpng iCCP warnings)
 * 2. Run pngquant (lossy 8-bit reduction) if available and mode==lossy
 * 3. Run zopflipng if available (best PNG recompressor), else optipng, else pngcrush
 * 4. Strip metadata again and move temp -> output
 */
class PngCompressor extends BaseCompressor
{
    protected function doCompress(string $input, string $output): bool
    {
        $quality = $this->option('quality', '65-85');
        $mode = $this->option('mode', 'lossy');

        // make a working copy to avoid damaging original
        $working = $this->tempFile($input, 'png_work');

        // Step 0: Strip metadata first (fix iCCP and remove ICC/EXIF)
        MetadataStripper::strip($working);

        // Step 1: pngquant (lossy palette reduction) - only for lossy mode
        $pngquant = $this->which('pngquant');
        $usedPngquant = false;
        if ($pngquant !== null && $mode === 'lossy') {
            // pngquant writes its own output file; create a new tmp for it
            $pngquantTmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('itf_pngquant_') . '.png';
            $cmd = [
                $pngquant,
                '--quality=' . $quality,
                '--speed', '1',
                '--output', $pngquantTmp,
                '--force',
                $working
            ];
            $proc = new Process($cmd);
            $proc->run();

            if ($proc->isSuccessful() && file_exists($pngquantTmp)) {
                // swap working -> pngquant output
                @unlink($working);
                $working = $pngquantTmp;
                $usedPngquant = true;
            } else {
                // fallback: keep original working file (pngquant failed)
                if (file_exists($pngquantTmp)) {
                    @unlink($pngquantTmp);
                }
            }
        }

        // Step 2: run zopflipng if available (preferred), otherwise optipng, otherwise pngcrush
        // zopflipng is part of zopfli project (binary name 'zopflipng')
        $zopflipng = $this->which('zopflipng');
        if ($zopflipng !== null) {
            // Run zopflipng with safe options
            $zopTmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('itf_zopf_') . '.png';
            $cmd = [
                $zopflipng,
                '--iterations=15',
                '--filters=01234mepb',
                '--lossy_8bit',    // safe if pngquant already applied; otherwise may convert to 8-bit
                '--strip',         // strip metadata
                $working,
                $zopTmp
            ];
            $proc = new Process($cmd);
            $proc->run();
            if ($proc->isSuccessful() && file_exists($zopTmp)) {
                @unlink($working);
                $working = $zopTmp;
            } else {
                if (file_exists($zopTmp)) {
                    @unlink($zopTmp);
                }
            }
        } else {
            // fallback to optipng
            $optipng = $this->which('optipng');
            if ($optipng !== null) {
                // optipng edits in-place or writes to same file; use -o7 for max optimization
                $proc = new Process([$optipng, '-o7', $working]);
                $proc->run();
            } else {
                // fallback to pngcrush if present
                $pngcrush = $this->which('pngcrush');
                if ($pngcrush !== null) {
                    $crushTmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('itf_crush_') . '.png';
                    // -rem all removes ancillary chunks, -brute for stronger but slower
                    $proc = new Process([$pngcrush, '-rem', 'allb', '-reduce', $working, $crushTmp]);
                    $proc->run();
                    if ($proc->isSuccessful() && file_exists($crushTmp)) {
                        @unlink($working);
                        $working = $crushTmp;
                    } else {
                        if (file_exists($crushTmp)) {
                            @unlink($crushTmp);
                        }
                    }
                }
                // else no additional optimizer found — keep working as-is
            }
        }

        // Final: ensure metadata stripped, and move to output
        MetadataStripper::strip($working);

        // Ensure output dir exists and is writable
        FileHelper::ensureWritableDir($output);

        // Use rename when possible; fallback to copy
        if (@rename($working, $output)) {
            return file_exists($output);
        }

        // rename may fail across filesystems; try copy
        if (@copy($working, $output)) {
            @unlink($working);
            return file_exists($output);
        }

        // as a last resort, try a GD save (re-encode)
        if (extension_loaded('gd')) {
            $img = @imagecreatefrompng($input);
            if ($img !== false) {
                imagesavealpha($img, true);
                imagepng($img, $output, 9);
                imagedestroy($img);
                MetadataStripper::strip($output);
                return file_exists($output);
            }
        }

        // failed
        throw new ImageTinifyException('PNG compression failed: could not generate output file.');
    }

    /**
     * Cross-platform which/search for a binary
     */
    protected function which(string $cmd): ?string
    {
        // try common names first (handles absolute/relative path if provided)
        if (file_exists($cmd) && is_executable($cmd)) {
            return $cmd;
        }

        $finder = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $proc = new Process([$finder, $cmd]);
        $proc->run();
        if ($proc->isSuccessful()) {
            $out = trim($proc->getOutput());
            if ($out !== '') {
                // return first path
                return explode(PHP_EOL, $out)[0];
            }
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
