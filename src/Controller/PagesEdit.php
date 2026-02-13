<?php

declare(strict_types=1);

namespace Kursova\Controller;

use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Kursova\Context;
use Kursova\FileManager;
use Kursova\PageManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

use React\Http\Message\Response;
use React\Mysql\MysqlResult;

use function assert;
use function count;
use function filter_var;
use function is_array;
use function React\Async\await;
use function React\Promise\all;
use function sprintf;
use function strlen;

use const UPLOAD_ERR_NO_FILE;

final readonly class PagesEdit
{
    public function __construct(
        private FileManager $fileManager,
        private PageManager $pageManager,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $context = $request->getAttribute(Context::class);
        assert($context instanceof Context);

        $id = $request->getAttribute('id');

        $submitted = $request->getMethod() === 'POST';

        $pathErrors = [];

        $page = null;
        $gallery = [];

        if ($submitted) {
            $post = $request->getParsedBody();
            $path = '';
            $title = '';
            $text = '';
            $includeInMenu = false;
            if (is_array($post)) {
                $path = $post['path'] ?? '';
                $title = $post['title'] ?? '';
                $text = $post['text'] ?? '';
                $includeInMenu = filter_var($post['include_in_menu'] ?? '', FILTER_VALIDATE_BOOLEAN);

                if ($id === null) {
                    if ($path === '') {
                        $pathErrors[] = 'Path must not be blank';
                    } else {
                        if ($path[0] === '/') {
                            $pathErrors[] = 'Path must not starts with /';
                        }
                        if (strlen($path) > 255) {
                            $pathErrors[] = 'Path must be less than 255 characters';
                        }
                    }
                }

                if (count($pathErrors) === 0) {
                    $connection = $context->getConnection();

                    $totalImages = 0;
                    await($connection->query('START TRANSACTION'));
                    try {
                        if ($id !== null) {
                            $queries = [];

                            $queries[] = $connection->query(
                                'UPDATE pages SET title = ?, text = ?, included_in_menu = ? WHERE id = ?',
                                [$title, $text, $includeInMenu, $id]
                            );


                            $filesSort = $post['filesSort'] ?? [];
                            if (count($filesSort) > 0) {
                                foreach ($filesSort as $index => $fileId) {
                                    $queries[] = $connection->query(
                                        'UPDATE pages_to_files SET order_index = ? WHERE page_id = ? AND file_id = ?',
                                        [$index, $id, $fileId]
                                    );
                                }
                            }

                            await(all($queries));

                            $result = await(
                                $connection->query(
                                    'SELECT COUNT(*) AS cnt FROM pages_to_files pageToFile WHERE pageToFile.page_id = ?',
                                    [$id]
                                )
                            );

                            $totalImages = $result->resultRows[0]['cnt'] ?? 0;
                        } else {
                            $result = await(
                                $connection->query('SELECT 1 AS ch  FROM pages WHERE path = ? FOR UPDATE', [$path])
                            );

                            if ($result->resultRows[0]['ch'] === '1') {
                                $pathErrors[] = sprintf('%s is already taken.', $path);
                                throw new InvalidArgumentException('Page with path "' . $path . '" already exists.');
                            }

                            $result = await(
                                $connection->query(
                                    'INSERT INTO pages (path, title, text, included_in_menu) VALUES (?, ?, ?, ?)',
                                    [
                                        $path,
                                        $title,
                                        $text,
                                        $includeInMenu,
                                    ]
                                )
                            );
                            $id = $result->insertId;
                        }

                        foreach ($request->getUploadedFiles()['gallery'] ?? [] as $i => $file) {
                            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                                continue;
                            }
                            assert($file instanceof UploadedFileInterface);

                            $fileId = $this->fileManager->upload($file, $connection);

                            await(
                                $connection->query(
                                    'INSERT INTO pages_to_files (page_id, file_id, order_index) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE order_index = order_index',
                                    [
                                        $id,
                                        $fileId,
                                        $totalImages + $i,
                                    ]
                                )
                            );
                        }

                        await($connection->query('COMMIT'));

                        return new Response(
                            StatusCodeInterface::STATUS_FOUND,
                            [
                                'Location' => '/admin/pages'
                            ]
                        );
                    } catch (\Throwable $ex) {
                        await($connection->query('ROLLBACK'));

                        if (!$ex instanceof InvalidArgumentException) {
                            throw $ex;
                        }
                    }
                }
            }

            $page = [
                'id' => $id,
                'path' => $path,
                'title' => $title,
                'text' => $text,
                'include_in_menu' => $includeInMenu,
            ];
        }

        if ($id !== null) {
            $connection = $context->getConnection();
            $result = await($connection->query('SELECT * FROM pages WHERE id = ?', [
                'id' => $id
            ]));
            assert($result instanceof MysqlResult);
            $page = $result->resultRows[0] ?? null;

            if ($page !== null) {
                $gallery = $this->pageManager->getGallery($page['id'], $connection);
            }
        }

        return $context->respond("pages_edit", [
            'errors' => [
                'path' => $pathErrors
            ],
            'page' => $page,
            'gallery' => $gallery
        ]);
    }
}