<?php
$this->layout('layout', ['title' => 'CMS', 'currentAdmin' => $currentAdmin, 'pagesInMenu' => $pagesInMenu]);
?>

<?php $this->push('styles') ?>
<link rel="stylesheet" href="/pages_admin_edit.css"/>
<?php $this->end() ?>

<form method="post" enctype="multipart/form-data">
    <label>
        <div>Path</div>
        <span>/
        <input type="text"
               name="path"
               <?php if ($page['id'] !== null): ?>disabled<?php endif; ?>
               value="<?= $this->e($page['path']) ?>"
        />
        </span>

        <?= $this->insert('partials/errors', ['errors' => $errors['path'] ?? []]); ?>
    </label>

    <label>
        Title
        <input type="text"
               name="title"
               value="<?= $this->e($page['title']) ?>"
        />
    </label>

    <label>
        Text
        <textarea name="text"><?= $this->e($page['text']) ?></textarea>
    </label>

    <label>

<span>
      <input type="checkbox"
             name="include_in_menu"
             <?php if ($page['included_in_menu']): ?>checked<?php endif; ?>
        />  Включи в менюто?!
</span>


    </label>

    <label>
        Gallery
        <input type="file" name="gallery[]" multiple accept="image/*"/>

    </label>
    <ol class="new-gallery" data-sortable>
        <?php foreach ($gallery as $image): ?>
            <li class="new-gallery__item" draggable="true">
                <input type="hidden" name="filesSort[]" value="<?= $image['id'] ?>"/>
                <img class="new-gallery__img" src="<?= $this->filePath($image, \Kursova\ImageSizeEnum::SMALL) ?>"/>
            </li>
        <?php endforeach; ?>
    </ol>

    <button type="submit">Save</button>
</form>


<script src="/admin.js"></script>
