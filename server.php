<?php

declare(strict_types=1);

use Clue\React\Redis\RedisClient;
use Kursova\AdminManager;
use Kursova\ContextMiddleware;
use Kursova\Controller\PagesEdit;
use Kursova\Controller\PagesListing;
use Kursova\Controller\Contact;
use Kursova\Controller\ContactAdmin;
use Kursova\Controller\Home;
use Kursova\Controller\Login;
use Kursova\Controller\Logout;
use Kursova\Controller\Page;
use Kursova\FileManager;
use Kursova\FilesMiddleware;
use League\Plates\Engine;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Factory;
use React\Mysql\MysqlClient;

use function React\Async\await;
use function React\Promise\all;

ini_set('post_max_size', '20M');
ini_set('memory_limit', '256M');

error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

$pool = new \ReactphpX\MySQL\Pool(
    uri: 'root:IyNHgJ4Zp34c8Gqk@192.168.58.2:30900/kursova?timeout=5',
    minConnections: 2,
    maxConnections: 10,
    waitQueue: 100,
    waitTimeout: 50,
);
$redis = new Clue\React\Redis\RedisClient('192.168.58.2:30901');

$engine = new Engine(__DIR__ . '/templates');

$filesystem = Factory::create();

$uploadDir = __DIR__ . '/public';
$fileManager = new FileManager($uploadDir, '/files', $filesystem);

$engine->registerFunction('filePath', [$fileManager, 'getUrl']);

$adminManager = new AdminManager();

$filesMiddleware = new FilesMiddleware($uploadDir, $filesystem);

$container = new FrameworkX\Container([
    Engine::class => $engine,
    \ReactphpX\MySQL\Pool::class => $pool,
    RedisClient::class => $redis,
    AdminManager::class => $adminManager,
    FileManager::class => $fileManager,
    AdapterInterface::class => $filesystem,
    FilesMiddleware::class => $filesMiddleware
]);


$app = new FrameworkX\App(
    $container,
    FilesMiddleware::class,
    ContextMiddleware::class,
);

if ($argv[1] ?? null === '--install') {
    echo 'Starting in INSTALL mode';

    echo 'Creating database' . PHP_EOL;
    $connection = await($pool->getConnection());
    assert($connection instanceof MysqlClient);

    $queries = [];

    $queries[] = $connection->query('DROP DATABASE kursova');
    $queries[] = $connection->query('CREATE DATABASE kursova');

    $queries[] = $connection->query("USE kursova");

    await(all($queries));

    $queries = [];

    $queries[] = $connection->query(
        'CREATE TABLE admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)'
    );

    $queries[] = $connection->query(
        'CREATE TABLE enquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    text TEXT NOT NULL,
    handled BOOLEAN NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);'
    );
    $queries[] = $connection->query(
        'CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
)'
    );

    await(all($queries));

    $adminManager->createUser('admin', 'admin', $connection);

    $pool->releaseConnection($connection);

    echo 'Done' . PHP_EOL;

    die;
} else {
    $app->get('/', Home::class);

    $app->map(['GET', 'POST'], '/contact-us', Contact::class);
    $app->map(['GET'], '/admin/pages', PagesListing::class);
    $app->map(['GET', 'POST'], '/admin/pages/edit', PagesEdit::class);
    $app->map(['GET', 'POST'], '/admin/pages/edit/{id}', PagesEdit::class);
    $app->map(['GET'], '/admin/contact-us', ContactAdmin::class);
    $app->map(['GET', 'POST'], '/login', Login::class);
    $app->map(['GET'], '/logout', Logout::class);
    $app->get('/{path}', Page::class);
}

$app->run();