<?php

declare(strict_types=1);

namespace Kursova\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use React\Http\Message\Response;

use function microtime;
use function sleep;

final readonly class DemoSync
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $start = microtime(true);

        sleep(10);

        return Response::plaintext('Тази заявка винаги ще отнеме 10 секунди. ' . (microtime(true) - $start));
    }
}