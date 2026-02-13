<?php

declare(strict_types=1);

namespace Kursova\Controller;

use Kursova\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use React\Http\Message\Response;

use function microtime;
use function React\Async\await;

final readonly class DemoAsync
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $start = microtime(true);

        $context = $request->getAttribute(Context::class);
        assert($context instanceof Context);

        $connection = $context->getConnection();
        await($connection->query('SELECT SLEEP(10)'));

        return Response::plaintext('Заявката отне: ' . (microtime(true) - $start));
    }
}