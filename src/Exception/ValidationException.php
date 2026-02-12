<?php
declare(strict_types=1);

namespace Kursova\Exception;

final class ValidationException extends \Exception
{
    public function __construct(string $message, public string $field)
    {
        parent::__construct($message);
    }
}