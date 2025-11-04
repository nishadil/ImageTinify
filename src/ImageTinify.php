<?php

namespace Nishadil\ImageTinify;

use Nishadil\ImageTinify\Compressor\PngCompressor;
use Nishadil\ImageTinify\Compressor\JpegCompressor;
use Nishadil\ImageTinify\Compressor\WebpCompressor;
use Nishadil\ImageTinify\Exceptions\ImageTinifyException;

/**
 * ImageTinify main entry point.
 *
 * Modes:
 *  - 'lossy'      → high compression, small size, slight data loss
 *  - 'lossless'   → no data loss, moderate reduction
 *  - 'smart'      → visually lossless, dimension-preserving lossy compression (TinyPNG style)
 */
class ImageTinify
{
    /**
     * Compress an image file.
     *
     * @param string $input  Path to input file
     * @param string|null $output Path to output file (optional)
     * @param array $options Options: mode (lossy|lossless|smart), quality (int|string)
     * @return bool|string
     *         - bool true when output path is given (file written successfully)
     *         - string binary content when output path is not given
     * @throws ImageTinifyException
     */
    public function compress(string $input, ?string $output = null, array $options = []): bool|string
    {
        if (!file_exists($input)) {
            throw new ImageTinifyException("Input file not found: {$input}");
        }

        // Normalize mode
        $mode = strtolower($options['mode'] ?? 'smart');
        if (!in_array($mode, ['lossy', 'lossless', 'smart'])) {
            throw new ImageTinifyException("Invalid mode: {$mode}. Use lossy, lossless, or smart.");
        }
        $options['mode'] = $mode;

        // Auto quality tuning for smart mode (balanced visual quality)
        if ($mode === 'smart' && empty($options['quality'])) {
            $options['quality'] = match (strtolower(pathinfo($input, PATHINFO_EXTENSION))) {
                'png'   => '65-85',
                'jpg', 'jpeg' => 75,
                'webp'  => 75,
                default => 80,
            };
        }

        $ext = strtolower(pathinfo($input, PATHINFO_EXTENSION));

        // pick compressor
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
            case 'avif':
                $compressor = new AvifCompressor();
                break;
            default:
                throw new ImageTinifyException("Unsupported file type: {$ext}");
        }

        // Temporary file if output not provided
        $tempOutput = false;
        if ($output === null) {
            $output = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('itf_') . '.' . $ext;
            $tempOutput = true;
        }

        // Perform compression
        $ok = $compressor->compress($input, $output, $options);
        if (!$ok || !file_exists($output)) {
            throw new ImageTinifyException("Compression failed for file: {$input}");
        }

        // In smart mode, verify dimensions are preserved
        if ($mode === 'smart' && extension_loaded('gd')) {
            $orig = getimagesize($input);
            $new  = getimagesize($output);
            if ($orig && $new && ($orig[0] !== $new[0] || $orig[1] !== $new[1])) {
                // Restore dimension lock violation (should never happen)
                throw new ImageTinifyException("Dimension mismatch after smart compression. Expected {$orig[0]}x{$orig[1]}, got {$new[0]}x{$new[1]}");
            }
        }

        // If output file path not provided → return binary string
        if ($tempOutput) {
            $data = @file_get_contents($output);
            @unlink($output);
            return $data;
        }

        // Normal mode: return success
        return true;
    }
}
