<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow"><?= e(t('footer.legal')) ?></p>
        <h1><?= e(t('legal.title')) ?></h1>
    </div>
</section>
<section class="section">
    <div class="wrap narrow legal">
        <?php require ROOT . '/views/partials/stay-info.php'; ?>
        <?php require ROOT . '/views/partials/stay-rules.php'; ?>
        <p><?= e(t('legal.pets')) ?></p>
        <p><?= e(t('legal.cancel')) ?></p>
        <p><?= e(t('prices.cleaning')) ?></p>
        <p><?= e(t('prices.caution')) ?></p>
        <p><?= e(t('prices.deposit')) ?></p>
        <p><?= e(t('prices.balance')) ?></p>
        <p><?= e(t('footer.iun')) ?></p>
        <p><?= e(t('footer.registry')) ?></p>
        <div id="cookies" class="terms-box">
            <h2><?= e(t('cookies.title')) ?></h2>
            <p><?= e(t('cookies.legal')) ?></p>
        </div>
    </div>
</section>
