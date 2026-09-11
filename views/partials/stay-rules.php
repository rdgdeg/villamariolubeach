<div class="house-rules">
    <h3><?= e(t('legal.golden_title')) ?></h3>
    <ul class="golden-list">
        <?php foreach (t_arr('legal.golden') as $rule): ?>
            <li><?= e($rule) ?></li>
        <?php endforeach; ?>
    </ul>
    <h3><?= e(t('prices.rules_title')) ?></h3>
    <p><?= e(t('legal.hours')) ?></p>
    <p><?= e(t('legal.checkin')) ?></p>
    <p><?= e(t('legal.meeting')) ?></p>
</div>
