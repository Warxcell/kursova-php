<?php

declare(strict_types=1);

namespace Kursova;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\NotExistInterface;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;
use React\Stream\ReadableResourceStream;

use function count;
use function end;
use function explode;
use function fopen;
use function in_array;
use function is_string;
use function React\Async\await;
use function str_replace;

final readonly class FilesMiddleware
{
    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];

    public function __construct(
        public string $publicPath,
        private AdapterInterface $filesystem
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        callable $next
    ): ResponseInterface|PromiseInterface|iterable {
        $path = $request->getUri()->getPath();

        $pathOnFS = $this->publicPath . $path;
        $file = await($this->filesystem->detect($pathOnFS));
        if ($file instanceof FileInterface) {
            $source = new ReadableResourceStream(PhpErrorException::verify(fopen($pathOnFS, 'r')));
            return new Response(
                200,
                [
                    'Cache-Control' => 'public, max-age=31536000, immutable',
                    'Content-Type' => $this->getMimeType($pathOnFS),
                ],
                $source
            );
        } elseif ($file instanceof NotExistInterface) {
            $parts = explode('.', $pathOnFS);
            $extension = end($parts);
            $sizeRaw = $parts[count($parts) - 2] ?? null;
            if (is_string($extension) && is_string($sizeRaw) && in_array($extension, self::IMAGE_EXTENSIONS)) {
                $size = ImageSizeEnum::tryFrom($sizeRaw);
                if ($size !== null) {
                    $realPath = str_replace('.' . $sizeRaw, '', $pathOnFS);
                    $this->resize($realPath, $size, $pathOnFS);

                    $source = new ReadableResourceStream(PhpErrorException::verify(fopen($pathOnFS, 'r')));
                    return new Response(
                        200,
                        [
                            'Cache-Control' => 'public, max-age=31536000, immutable',
                            'Content-Type' => $this->getMimeType($pathOnFS),
                        ],
                        $source
                    );
                }
            }
        }

        return $next($request);
    }

    private function getMimeType(string $path): string
    {
        $parts = explode('.', $path);
        $extension = end($parts);

        return match (is_string($extension) ? $extension : '') {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'css' => 'text/css',
            'js' => 'application/javascript',
            default => 'application/octet-stream',
        };
    }

    private function resize(string $path, ImageSizeEnum $size, string $outputPath): void
    {
        $img = new \Imagick($path);
        $img->autoOrient();

        [$width, $height] = $size->getSize();
        $img->thumbnailImage($width, $height, true, true);

        $img->setImageFormat($img->getImageFormat());
        $img->stripImage();

        $img->writeImage($outputPath);
        $img->clear();
    }
}