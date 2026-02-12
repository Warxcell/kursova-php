<!DOCTYPE html>
<html lang="bg">
<head>
    <title><?= $this->e($title) ?></title>

    <?php $this->unshift('styles') ?>
    <link rel="stylesheet" href="/reset.css"/>
    <link rel="stylesheet" href="/layout.css"/>
    <?php $this->end() ?>

    <?= $this->section('styles') ?>
</head>
<body>

<header>
    <div class="container">
        <ul class="main-navigation">
            <li class="main-navigation-item"><a href="/">Начало</a></li>
            <li class="main-navigation-item"><a href="/contact-us">Свържи се с нас</a></li>
            <?php foreach ($pagesInMenu as $page): ?>
                <li class="main-navigation-item">
                    <a href="<?= $this->e($page['path']) ?>">
                        <?= $this->e($page['title']) ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <?php if ($currentAdmin): ?>
                <li class="main-navigation-divider"></li>
                <li class="main-navigation-item admin-item"><a href="/admin/contact-us">Запитвания</a></li>
                <li class="main-navigation-item admin-item"><a href="/admin/pages">Страници</a></li>
                <li class="main-navigation-divider"></li>
                <li class="main-navigation-item admin-greeting">Здравейте, <?= $currentAdmin->username ?></li>
                <li class="main-navigation-item"><a href="/logout">Logout</a></li>
            <?php else: ?>
                <li class="main-navigation-item"><a href="/login">Login</a></li>
            <?php endif; ?>
        </ul>
    </div>
</header>

<main>
    <div class="container">
        <?= $this->section('content') ?>
    </div>
</main>

<footer>
</footer>
</body>
</html>