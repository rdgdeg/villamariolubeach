<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow"><?= e(t('nav.contact')) ?></p>
        <h1><?= e(t('contact.title')) ?></h1>
    </div>
</section>
<section class="section">
    <div class="wrap narrow">
        <?php if ($msg = flash('success')): ?><p class="form-success"><?= e($msg) ?></p><?php endif; ?>
        <?php if ($msg = flash('error')): ?><p class="form-error"><?= e($msg) ?></p><?php endif; ?>
        <form method="post" action="<?= e(base_url('api/contact')) ?>" class="contact-form">
            <?= Csrf::field() ?>
            <label class="hp" aria-hidden="true">
                <input type="text" name="company" tabindex="-1" autocomplete="off">
            </label>
            <label><?= e(t('contact.name')) ?>
                <input type="text" name="name" required>
            </label>
            <label><?= e(t('contact.email')) ?>
                <input type="email" name="email" required>
            </label>
            <label><?= e(t('contact.phone')) ?>
                <input type="tel" name="phone">
            </label>
            <label><?= e(t('contact.subject')) ?>
                <input type="text" name="subject">
            </label>
            <label><?= e(t('contact.message')) ?>
                <textarea name="message" rows="6" required></textarea>
            </label>
            <button class="btn btn-terracotta" type="submit"><?= e(t('contact.send')) ?></button>
        </form>
        <p class="center"><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p>
    </div>
</section>
