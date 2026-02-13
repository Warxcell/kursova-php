<?php

declare(strict_types=1);

namespace Kursova\Controller;

use Kursova\Context;
use Kursova\PageManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function assert;

final readonly class PagesListing
{
    public function __construct(
        private PageManager $pageManager
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $request->getAttribute(Context::class);
        assert($context instanceof Context);

        $connection = $context->getConnection();
        $result = $this->pageManager->getPages($connection);

        return $context->respond("pages_listing", [
            'pages' => $result
        ]);
    }
}