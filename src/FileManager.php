<?php
declare(strict_types=1);

namespace Kursova;

use http\Exception\InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;

use React\Filesystem\AdapterInterface;
use React\Mysql\MysqlClient;
use React\Mysql\MysqlResult;

use function assert;
use function end;
use function explode;
use function is_string;
use function md5;
use function React\Async\await;

use function sprintf;

use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

/**
 * @phpstan-type File array{id: string, hash: string, original_filename: string, size: numeric-string, mime_type: string}
 */
final readonly class FileManager
{
    private string $uploadDir;

    public function __construct(
        string $uploadDir,
        public string $publicPath,
        private AdapterInterface $adapter
    ) {
        $this->uploadDir = $uploadDir . $publicPath;
    }

    /**
     * @param File $file
     */
    public function getUrl(array $file, ?ImageSizeEnum $size = null): string
    {
        $parts = explode('.', $file['original_filename']);
        $extension = end($parts);

        if ($size !== null) {
            return sprintf('%s/%s.%s.%s', $this->publicPath, $file['hash'], $size->value, $extension);
        } else {
            return sprintf('%s/%s.%s', $this->publicPath, $file['hash'], $extension);
        }
    }

    public function upload(UploadedFileInterface $file, MysqlClient $connection): int
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Error during file upload: ' . $file->getError());
        }
        $contents = $file->getStream()->getContents();
        $hashed = md5($contents);

        $result = await($connection->query('SELECT id FROM files file WHERE file.hash = ?', [$hashed]));
        assert($result instanceof MysqlResult);

        return (int)($result->resultRows[0]['id'] ?? (function () use (
            $connection,
            $hashed,
            $file,
            $contents
        ) {
            $result = await(
                $connection->query(
                    'INSERT INTO files (hash, original_filename, size, mime_type) VALUES(?, ?, ?, ?)',
                    [
                        $hashed,
                        $file->getClientFilename(),
                        $file->getSize(),
                        $file->getClientMediaType(),
                    ]
                )
            );
            assert($result instanceof MysqlResult);

            $parts = explode('.', $file->getClientFilename());
            $extension = end($parts);

            await($this->adapter->file($this->uploadDir . '/' . $hashed . '.' . $extension)->putContents($contents));

            return $result->insertId;
        })());
    }
}