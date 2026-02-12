<?php
declare(strict_types=1);

namespace Kursova;

use React\Mysql\MysqlClient;
use React\Mysql\MysqlResult;

use React\Promise\Promise;

use function assert;
use function filter_var;
use function React\Async\await;

use const PHP_EOL;

/**
 * @phpstan-import-type File from FileManager
 * @phpstan-type Page array{id: string, title: string, text: string, includeInMenu: boolean}
 */
class PageManager
{
    /**
     * @return Page[]
     */
    public function getPagesInMenu(MysqlClient $connection): array
    {
        return await(
            new Promise(static function (callable $resolve) use ($connection) {
                $pages = [];
                $stream = $connection->queryStream(
                    <<<SQL
SELECT id, path, title, text, included_in_menu FROM pages page WHERE page.included_in_menu = 1
SQL,
                );
                $stream->on('data', static function (array $row) use (&$pages): void {
                    $row['path'] = '/' . $row['path'];
                    $row['included_in_menu'] = filter_var($row['included_in_menu'], FILTER_VALIDATE_BOOLEAN);
                    $pages[] = $row;
                });
                $stream->on('end', static function () use ($resolve, &$pages): void {
                    $resolve($pages);
                });
            })
        );
    }

    /**
     * @return Page|null
     */
    public function getPageBypath(string $path, MysqlClient $connection): ?array
    {
        $result = await(
            $connection->query(
                <<<SQL
                SELECT * FROM pages page WHERE page.path = ?
                SQL,
                [
                    $path,
                ]

            )
        );

        return $result->resultRows[0] ?? null;
    }

    /**
     * @return File[]
     * @throws \Throwable
     */
    public function getGallery(string|int $pageId, MysqlClient $connection): array
    {
        $result = await(
            $connection->query(
                <<<SQL
SELECT file.* FROM files file 
JOIN pages_to_files pageToFile ON pageToFile.file_id = file.id
WHERE pageToFile.page_id = ?
ORDER BY pageToFile.order_index
SQL,
                [
                    'id' => $pageId
                ]
            )
        );
        return $result->resultRows;
    }
}