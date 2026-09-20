<div class="terms-box">
    <h3><?= e(t('book.terms_title')) ?></h3>
    <?php foreach (t_arr('book.info_letter') as $line): ?>
        <p><?= e($line) ?></p>
    <?php endforeach; ?>
    <p class="terms-sign">
        <?= e(t('book.info_sign_name')) ?><br>
        <?= e(t('book.info_sign_role')) ?><br>
        <?= e(t('book.info_sign_email')) ?>
    </p>
</div>
