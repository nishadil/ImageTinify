<?php

namespace Nishadil\ImageTinify\Utils;

class MetadataStripper
{
    /**
     * Strip metadata using PHP GD as fallback.
     * For JPEGs, using GD and re-saving removes EXIF.
     */
    public static function strip(string $filePath): void
    {
        $ext = FileHelper::guessExtension($filePath);

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                self::stripJpeg($filePath);
                break;
            case 'png':
            case 'webp':
                // Re-save with GD/Imagick to ensure removal of ancillary chunks
                self::stripGeneric($filePath);
                break;
            default:
                // do nothing
        }
    }

    protected static function stripJpeg(string $path): void
    {
        if (!extension_loaded('gd')) {
            return;
        }
        $img = @imagecreatefromjpeg($path);
        if ($img === false) {
            return;
        }
        imagejpeg($img, $path, 90);
        imagedestroy($img);
    }

    protected static function stripGeneric(string $path): void
    {
        if (!extension_loaded('gd')) {
            return;
        }

        $ext = FileHelper::guessExtension($path);
        switch ($ext) {
            case 'png':
                $img = @imagecreatefrompng($path);
                if ($img !== false) {
                    imagesavealpha($img, true);
                    imagepng($img, $path);
                    imagedestroy($img);
                }
                break;
            case 'webp':
                $img = @imagecreatefromwebp($path);
                if ($img !== false) {
                    imagewebp($img, $path);
                    imagedestroy($img);
                }
                break;
        }
    }
}
