<?php
$this->layout('layout', ['title' => 'CMS', 'currentAdmin' => $currentAdmin, 'pagesInMenu' => $pagesInMenu]);
?>

<?php $this->push('styles') ?>
<link rel="stylesheet" href="/pages_admin_listing.css"/>
<?php $this->end() ?>

<div class="page-listing">
    <a href="/admin/pages/edit">Add new</a>

    <?php foreach ($pages as $page): ?>
        <a href="/admin/pages/edit/<?= $page['id'] ?>"><?= $this->e($page['title']) ?> (<?= $this->e($page['path']) ?>
            )</a>
    <?php endforeach; ?>
</div>
