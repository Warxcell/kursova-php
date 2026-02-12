<?php

declare(strict_types=1);

namespace Kursova;

use Clue\React\Redis\RedisClient;
use Fig\Http\Message\StatusCodeInterface;
use Kursova\Model\Admin;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use React\Mysql\MysqlClient;
use ReactphpX\MySQL\Pool;

use function json_decode;
use function json_encode;
use function random_bytes;
use function React\Async\await;

use const JSON_THROW_ON_ERROR;

final class Context
{
    private MysqlClient|null $connection = null;

    private readonly ?string $sessionId;

    private readonly bool $hasPreviousSession;

    /** @var array<string, mixed>|null */
    private ?array $session = null;

    private ?Admin $admin = null;

    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly Pool $pool,
        private readonly AdminManager $adminManager,
        private readonly Engine $engine,
        private readonly RedisClient $redisClient,
        private readonly PageManager $pageManager,
    ) {
        $sessionId = $this->request->getCookieParams()['sessid'] ?? null;
        $this->hasPreviousSession = $sessionId !== null;
        $this->sessionId = $sessionId ?? bin2hex(random_bytes(32));
    }

    public function accessDenied(): ResponseInterface
    {
        return $this->respond('access_denied', status: StatusCodeInterface::STATUS_FORBIDDEN);
    }

    public function notFound(): ResponseInterface
    {
        return $this->respond('not_found', status: StatusCodeInterface::STATUS_NOT_FOUND);
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function getConnection(): MysqlClient
    {
        if ($this->connection === null) {
            $this->connection = await($this->pool->getConnection());
        }

        return $this->connection;
    }

    public function isSessionStarted(): bool
    {
        return $this->session !== [];
    }

    /**
     * @return array<string, mixed>
     */
    private function &getSession(): array
    {
        if ($this->session === null) {
            if (!$this->hasPreviousSession) {
                $this->session = [];
            } else {
                $serialized = await($this->redisClient->callAsync('get', $this->sessionId)) ?? '';
                $this->session = json_decode($serialized, associative: true, flags: JSON_THROW_ON_ERROR);
            }
        }

        return $this->session;
    }

    public function getCurrentAdmin(): Admin|null
    {
        $session = $this->getSession();
        $adminId = $session['adminId'] ?? null;
        if ($adminId === null) {
            return null;
        }
        if ($this->admin === null) {
            $this->admin = $this->adminManager->getUser($adminId, $this->getConnection());
        }
        return $this->admin;
    }

    public function loginAdmin(string $username, string $password): Admin|null
    {
        $this->admin = $this->adminManager->loginUser($username, $password, $this->getConnection());
        if ($this->admin !== null) {
            $session = &$this->getSession();
            $session['adminId'] = $this->admin->id;
        }

        return $this->admin;
    }

    public function logout(): void
    {
        $this->getSession()['adminId'] = null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        $data['currentAdmin'] = $this->getCurrentAdmin();
        $data['pagesInMenu'] = $this->pageManager->getPagesInMenu($this->getConnection());

        return $this->engine->render($template, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function respond(
        string $template,
        array $data = [],
        int $status = StatusCodeInterface::STATUS_OK
    ): ResponseInterface {
        return new Response(
            $status,
            array('Content-Type' => 'text/html; charset=utf-8'),
            $this->render($template, $data)
        );
    }

    public function __destruct()
    {
        if ($this->connection !== null) {
            $this->pool->releaseConnection($this->connection);
        }

        if ($this->isSessionStarted()) {
            $this->redisClient->callAsync(
                'set',
                $this->sessionId,
                json_encode($this->getSession(), flags: JSON_THROW_ON_ERROR)
            );
        }
    }
}