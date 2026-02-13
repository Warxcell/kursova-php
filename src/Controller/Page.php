<?php

declare(strict_types=1);

namespace Kursova\Controller;

use Kursova\Context;
use Kursova\PageManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function assert;

final readonly class Page
{
    public function __construct(
        private PageManager $pageManager,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $request->getAttribute(Context::class);
        assert($context instanceof Context);

        /** @var string $path */
        $path = $request->getAttribute('path');

        $connection = $context->getConnection();

        $page = $this->pageManager->getPageBypath($path, $connection);

        if ($page === null) {
            return $context->notFound();
        }

        $gallery = $this->pageManager->getGallery($page['id'], $connection);

        return $context->respond("page", [
            'page' => $page,
            'gallery' => $gallery,
        ]);
    }
}