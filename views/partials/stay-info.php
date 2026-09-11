<div class="terms-box">
    <h3><?= e(t('legal.info_title')) ?></h3>
    <p><?= e(t('legal.waste')) ?></p>
    <p><?= e(t('legal.fridge')) ?></p>
    <p><?= e(t('legal.beds')) ?></p>
    <p><?= e(t('legal.extra')) ?></p>
    <p><strong><?= e(t('legal.checkin')) ?></strong></p>
    <p><?= e(t('legal.hours')) ?></p>
    <p><?= e(t('legal.meeting')) ?></p>
    <?php if (setting('shardana_maps')): ?>
        <p><a href="<?= e(setting('shardana_maps')) ?>" target="_blank" rel="noopener"><?= e(t('legal.meeting_cta')) ?></a></p>
    <?php endif; ?>
    <p><strong><?= e(t('legal.deposit_stay')) ?></strong></p>
</div>
