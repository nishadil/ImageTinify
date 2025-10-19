<?php

namespace Nishadil\ImageTinify\Exceptions;

use Exception;

/**
 * Class ImageTinifyException
 *
 * Custom exception class for ImageTinify library.
 *
 * Thrown whenever the library encounters an error such as:
 * - Unsupported image format
 * - Missing input file
 * - Missing GD extension or external compression tool
 * - Failed image read/write operations
 *
 * Example:
 * ```php
 * throw new ImageTinifyException("Failed to compress image");
 * ```
 *
 * @package Nishadil\ImageTinify\Exceptions
 */
class ImageTinifyException extends Exception
{
    /**
     * Create a new ImageTinify exception instance.
     *
     * @param string $message  The exception message.
     * @param int $code        The error code (optional).
     * @param \Throwable|null $previous Previous throwable for nested exceptions.
     */
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
