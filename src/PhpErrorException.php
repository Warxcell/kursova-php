<?php
declare(strict_types=1);

namespace Kursova;

use RuntimeException;

use function error_get_last;

final class PhpErrorException extends RuntimeException
{
    /**
     * @template T
     *
     * @param T|false $response
     *
     * @return T
     *
     * @throws self
     */
    public static function verify(mixed $response): mixed
    {
        if ($response === false) {
            $error = error_get_last();
            $message = $error ? $error['message'] : 'Unknown error occurred';

            throw new self($message);
        }

        return $response;
    }
}
