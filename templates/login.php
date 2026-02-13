<?php
$this->layout('layout', ['title' => 'Вхов в системата', ...$context->getLayoutParams()]) ?>

<?php $this->push('styles') ?>
<link rel="stylesheet" href="/form.css"/>
<?php $this->end() ?>


<form method="post">
    <h1>Login</h1>
    <label>
        Username:
        <input type="text" name="username" required/>
    </label>
    <label>
        Password:
        <input type="password" name="password" required/>
    </label>

    <button type="submit">Login</button>
</form>