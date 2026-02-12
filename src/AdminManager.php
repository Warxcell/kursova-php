<?php

declare(strict_types=1);

namespace Kursova;

use Kursova\Model\Admin;
use React\Mysql\MysqlClient;
use React\Mysql\MysqlResult;

use function assert;
use function password_verify;
use function React\Async\await;

class AdminManager
{
    public function __construct()
    {
    }

    public function createUser(string $username, string $password, MysqlClient $client): void
    {
        $passwordHashed = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64MB
            'time_cost' => 4,     // Number of iterations
            'threads' => 2      // Number of parallel threads
        ]);

        await($client->query('INSERT INTO admins (username, password) VALUES (?, ?)', [
            $username,
            $passwordHashed
        ]));
    }

    public function loginUser(string $username, string $password, MysqlClient $client): Admin|null
    {
        $result = await($client->query('SELECT id,username,password FROM admins WHERE username = ?', [$username]));

        $user = $result->resultRows[0] ?? null;

        if ($user === null || !password_verify($password, $user['password'])) {
            return null;
        }
        return new Admin(id: (int)$user['id'], username: $user['username']);
    }

    public function getUser(int $id, MysqlClient $client): Admin|null
    {
        $result = await($client->query('SELECT id,username FROM admins WHERE id = ?', [$id]));
        assert($result instanceof MysqlResult);

        $user = $result->resultRows[0] ?? null;
        if ($user === null) {
            return null;
        }
        return new Admin(id: (int)$user['id'], username: $user['username']);
    }
}