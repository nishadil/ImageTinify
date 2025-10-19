<?php

namespace Nishadil\ImageTinify\Compressor;

use Nishadil\ImageTinify\Utils\FileHelper;
use Nishadil\ImageTinify\Exceptions\ImageTinifyException;

abstract class BaseCompressor
{
    protected array $options = [];

    public function compress(string $input, string $output, array $options = []): bool
    {
        if (!file_exists($input)) {
            throw new ImageTinifyException("Input file does not exist: {$input}");
        }

        FileHelper::ensureWritableDir($output);
        $this->options = $options;

        return $this->doCompress($input, $output);
    }

    abstract protected function doCompress(string $input, string $output): bool;

    protected function option(string $key, $default = null)
    {
        return $this->options[$key] ?? $default;
    }
}
