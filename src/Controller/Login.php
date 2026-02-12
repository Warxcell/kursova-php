<?php

declare(strict_types=1);

namespace Kursova\Controller;

use Fig\Http\Message\StatusCodeInterface;
use Kursova\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

final readonly class Login
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $request->getAttribute(Context::class);
        assert($context instanceof Context);

        if ($request->getMethod() === 'POST') {
            $body = $request->getParsedBody();
            $username = $body['username'] ?? '';
            $password = $body['password'] ?? '';
            $admin = $context->loginAdmin($username, $password);

            if ($admin !== null) {
                return new Response(
                    StatusCodeInterface::STATUS_FOUND,
                    ['Location' => '/']
                );
            }
        }

        return $context->respond("login");
    }
}