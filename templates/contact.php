<?php

$this->layout('layout', ['title' => 'Запитвания', 'currentAdmin' => $currentAdmin, 'pagesInMenu' => $pagesInMenu]); ?>

<?php $this->push('styles') ?>
<link rel="stylesheet" href="/form.css"/>
<?php $this->end() ?>

<form method="post">
    <h1>Свържете се с нас.</h1>
    <?php
    if ($submitted && $valid): ?>
        <div>Вашето запитване е изпратено. Очаквайте обратно включване.</div>
    <?php
    else: ?>
        <label>
            Вашето име:
            <input type="text" name="name" value="<?= $this->e($data['name'] ?? '') ?>"/>
            <?= $this->insert('partials/errors', ['errors' => $errors['name'] ?? []]); ?>
        </label>
        <label>
            Вашият имейл:
            <input type="email" name="email" value="<?= $this->e($data['email'] ?? '') ?>"/>
            <?= $this->insert('partials/errors', ['errors' => $errors['email'] ?? []]); ?>
        </label>
        <label>
            Вашето запитване
            <textarea name="text"><?= $this->e($data['text'] ?? '') ?></textarea>
            <?= $this->insert('partials/errors', ['errors' => $errors['text'] ?? []]); ?>
        </label>

        <button type="submit">Изпрати запитването</button>
    <?php
    endif; ?>
</form>