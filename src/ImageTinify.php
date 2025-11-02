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
	 * @param string|null $output Path to output file (optional)
	 * @param array $options Options: mode (lossy|lossless), quality (int|string)
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

        $ext = strtolower(pathinfo($input, PATHINFO_EXTENSION));

        // pick correct compressor
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

        // if no output provided, create a temporary file
        $tempOutput = false;
        if ($output === null) {
            $output = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('itf_') . '.' . $ext;
            $tempOutput = true;
        }

        // perform compression
        $ok = $compressor->compress($input, $output, $options);

        if (!$ok || !file_exists($output)) {
            throw new ImageTinifyException("Compression failed: {$input}");
        }

        // If temp mode, return binary data and clean up
        if ($tempOutput) {
            $data = @file_get_contents($output);
            @unlink($output);
            return $data;
        }

        // Normal mode
        return true;
    }

	protected function defaultOutputPath(string $input): string
	{
		$info = pathinfo($input);
		return $info['dirname'] . DIRECTORY_SEPARATOR . $info['filename'] . '-tinified.' . $info['extension'];
	}
}
