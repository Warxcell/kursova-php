<?php

declare(strict_types=1);

namespace Kursova;

enum ImageSizeEnum: string
{
    case SMALL = 'small';

    /** @return array{int, int} */
    public function getSize(): array
    {
        return match ($this) {
            self::SMALL => [100, 100],
        };
    }
}
