<?php
$this->layout('layout', ['title' => 'Запитвания', 'currentAdmin' => $currentAdmin, 'pagesInMenu' => $pagesInMenu])
?>


<?php $this->push('styles') ?>
<link rel="stylesheet" href="/enquiries.css"/>
<?php $this->end() ?>

<div class="enquiries-list">
    <?php
    foreach ($enquiries as $enquiry): ?>
        <div class="enquiry-card">
            <div class="enquiry-header">
                <span class="enquiry-id">#<?= $this->e($enquiry['id']) ?></span>
                <span class="enquiry-name"><?= $this->e($enquiry['name']) ?></span>
                <span class="enquiry-email"><?= $this->e($enquiry['email']) ?></span>
            </div>

            <div class="enquiry-text">
                <?= $this->e($enquiry['text']) ?>
            </div>

            <div class="enquiry-actions">
                <?php
                if (filter_var($enquiry['handled'], FILTER_VALIDATE_BOOL)): ?>
                    <span class="enquiry-status handled">✓ Обработено</span>
                <?php
                else: ?>
                    <form class="enquiry-form">
                        <button type="submit" name="id" value="<?= $this->e($enquiry['id']) ?>">Отбележи като обработено
                        </button>
                    </form>
                <?php
                endif; ?>
            </div>
        </div>
    <?php
    endforeach; ?>
</div>