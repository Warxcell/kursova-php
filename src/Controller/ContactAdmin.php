<?php

declare(strict_types=1);

namespace Kursova\Controller;

use Kursova\Context;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use React\Mysql\MysqlResult;

use function assert;
use function React\Async\await;

final readonly class ContactAdmin
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $request->getAttribute(Context::class);
        assert($context instanceof Context);

        if ($context->getCurrentAdmin() === null) {
            return $context->accessDenied();
        }

        $connection = $context->getConnection();

        $queryParams = $request->getQueryParams();
        $id = $queryParams['id'] ?? null;
        if ($id !== null) {
            await($connection->query('UPDATE enquiries SET handled = 1 WHERE id = ?', [
                $id
            ]));
        }

        $enquiries = await($connection->query('SELECT * FROM enquiries ORDER BY created_at DESC'));
        assert($enquiries instanceof MysqlResult);

        return $context->respond('contact_admin', ['enquiries' => $enquiries->resultRows]);
    }
}