<?php

declare(strict_types=1);

namespace Kursova;

use Clue\React\Redis\RedisClient;
use Generator;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;
use ReactphpX\MySQL\Pool;

use function sprintf;

final readonly class ContextMiddleware
{
    public function __construct(
        private Pool $pool,
        private AdminManager $userManager,
        private Engine $engine,
        private RedisClient $redisClient,
        private PageManager $pageManager
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        callable $next
    ): ResponseInterface|PromiseInterface|iterable {
        $context = new Context(
            $request,
            $this->pool,
            $this->userManager,
            $this->engine,
            $this->redisClient,
            $this->pageManager
        );

        $response = $next($request->withAttribute(Context::class, $context));

        if ($response instanceof PromiseInterface) {
            return $response->then(fn($response) => $this->handleResponse($context, $response));
        } elseif ($response instanceof Generator) {
            return (fn() => $this->handleResponse($context, yield from $response))();
        } else {
            return $this->handleResponse($context, $response);
        }
    }

    private function handleResponse(Context $context, ResponseInterface $response): ResponseInterface
    {
        if ($context->isSessionStarted()) {
            return $response->withHeader(
                'Set-Cookie',
                sprintf('sessid=%s; Path=/; HttpOnly; SameSite=Lax', $context->getSessionId())
            );
        }
        return $response;
    }
}