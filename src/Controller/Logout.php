<?php

declare(strict_types=1);

namespace Kursova\Controller;

use Fig\Http\Message\StatusCodeInterface;
use Kursova\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

final readonly class Logout
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $request->getAttribute(Context::class);
        assert($context instanceof Context);

        $context->logout();

        return new Response(
            StatusCodeInterface::STATUS_FOUND,
            ['Location' => '/']
        );
    }
}