<?php

use Kursova\ImageSizeEnum;

$this->layout(
        'layout',
        ['title' => 'Начална страница', ...$context->getLayoutParams()]
) ?>


<?php $this->push('styles') ?>
<link rel="stylesheet" href="/gallery.css"/>
<?php $this->end() ?>


<h1><?= $this->e($page['title']) ?></h1>

<div><p><?= $this->e($page['text']) ?></p></div>

<?php if (count($gallery) > 0): ?>
    <div class="gallery">
        <div class="main-img"><img src="<?= $this->filePath($gallery[0]) ?>" alt="main image"></div>
        <div class="gallery-list">
            <?php foreach ($gallery as $image): ?>
                <div class="gallery-list-item">
                    <img src="<?= $this->filePath($image, ImageSizeEnum::SMALL) ?>"
                         data-full-src="<?= $this->filePath($image) ?>"
                         alt="<?= $this->e($image['original_filename']) ?>"/>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<script src="gallery.js"></script>