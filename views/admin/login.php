<div class="login-card">
    <img src="<?= e(asset('img/logo/logo-gold.jpg')) ?>" alt="">
    <h1>Administration</h1>
    <?php if ($msg = flash('error')): ?><p class="flash err"><?= e($msg) ?></p><?php endif; ?>
    <form method="post">
        <?= Csrf::field() ?>
        <label>Identifiant
            <input type="text" name="username" required autofocus>
        </label>
        <label>Mot de passe
            <input type="password" name="password" required>
        </label>
        <button type="submit">Connexion</button>
    </form>
</div>
