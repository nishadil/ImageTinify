<?php

namespace Nishadil\ImageTinify\Utils;

class FileHelper
{
    public static function ensureWritableDir(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException("Failed to create directory: {$dir}");
            }
        }

        if (file_exists($path) && !is_writable($path)) {
            throw new \RuntimeException("File exists and is not writable: {$path}");
        }
    }

    public static function guessExtension(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: '');
    }

    public static function backupOriginal(string $src): string
    {
        $bak = $src . '.bak';
        copy($src, $bak);
        return $bak;
    }
}
