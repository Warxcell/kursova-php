<?php

declare(strict_types=1);

namespace Kursova\Model;

final readonly class Admin
{
    public function __construct(
        public int $id,
        public string $username,
    ) {
    }
}