<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <link rel="canonical" href="<?= e($canonical ?? url_for($page ?? 'home')) ?>">
    <?php
    $seoPage = $page ?? 'home';
    $seoCanon = $canonical ?? url_for($seoPage);
    $seoImage = $ogImage ?? Seo::image();
    $seoLocale = Seo::LOCALES[current_lang()] ?? 'en_GB';
    ?>
    <?php foreach (I18n::LANGS as $hreflang): ?>
        <link rel="alternate" hreflang="<?= e($hreflang) ?>" href="<?= e(url_for($seoPage, [], $hreflang)) ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= e(url_for($seoPage, [], config('default_lang', 'en'))) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Villa Mariolu Beach">
    <meta property="og:locale" content="<?= e($seoLocale) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:url" content="<?= e($seoCanon) ?>">
    <meta property="og:image" content="<?= e($seoImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title) ?>">
    <meta name="twitter:description" content="<?= e($description) ?>">
    <meta name="twitter:image" content="<?= e($seoImage) ?>">
    <script type="application/ld+json"><?= json_encode(Seo::jsonLd($seoPage), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
    <link rel="icon" href="<?= e(asset('img/logo/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400&family=Roboto:wght@400;500;600&family=Roboto+Slab:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>?v=<?= e((string) filemtime(ROOT . '/assets/css/app.css')) ?>">
    <script>window.VMB = {
        lang: <?= json_encode(current_lang()) ?>,
        api: <?= json_encode(base_url('api')) ?>,
        csrf: <?= json_encode(Csrf::token()) ?>,
        i18n: {
            weekdays: <?= json_encode(t_arr('calendar.weekdays')) ?>,
            months: <?= json_encode(t_arr('calendar.months')) ?>,
            available: <?= json_encode(t('calendar.available')) ?>,
            request: <?= json_encode(t('calendar.request')) ?>,
            unavailable: <?= json_encode(t('calendar.unavailable')) ?>,
            turnover: <?= json_encode(t('calendar.turnover')) ?>,
            nights: <?= json_encode(t('book.nights')) ?>,
            rental: <?= json_encode(t('book.rental')) ?>,
            discount: <?= json_encode(t('book.discount')) ?>,
            cleaning: <?= json_encode(t('book.cleaning')) ?>,
            total: <?= json_encode(t('book.total')) ?>,
            deposit: <?= json_encode(t('book.deposit')) ?>,
            balance: <?= json_encode(t('book.balance')) ?>,
            caution: <?= json_encode(t('book.caution')) ?>,
            checkin_from: <?= json_encode(t('book.checkin_from')) ?>,
            checkout_until: <?= json_encode(t('book.checkout_until')) ?>,
            nightly_rates: <?= json_encode(t('book.nightly_rates')) ?>,
            summary: <?= json_encode(t('book.summary')) ?>,
            errors: <?= json_encode(t_arr('book.errors')) ?>,
            success: <?= json_encode(t('book.success')) ?>
        }
    };</script>
</head>
<body>
    <a class="skip" href="#main">Aller au contenu</a>
    <header class="site-header" id="top">
        <div class="header-inner">
            <a class="logo" href="<?= e(url_for('home')) ?>">
                <img src="<?= e(asset('img/logo/logo-white.jpg')) ?>" alt="Villa Mariolu Beach Budoni">
            </a>
            <nav class="nav" id="nav">
                <a href="<?= e(url_for('home')) ?>"><?= e(t('nav.home')) ?></a>
                <a href="<?= e(url_for('home')) ?>#villa"><?= e(t('nav.villa')) ?></a>
                <a href="<?= e(url_for('home')) ?>#galerie"><?= e(t('nav.gallery')) ?></a>
                <a href="<?= e(url_for('home')) ?>#budoni"><?= e(t('nav.budoni')) ?></a>
                <a href="<?= e(url_for('home')) ?>#tarifs"><?= e(t('nav.prices')) ?></a>
                <a href="<?= e(url_for('home')) ?>#faq"><?= e(t('nav.faq')) ?></a>
                <a class="nav-book-mobile" href="<?= e(url_for('reserver')) ?>"><?= e(t('nav.book')) ?></a>
            </nav>
            <div class="header-tools">
                <a class="nav-cta" href="<?= e(url_for('reserver')) ?>"><?= e(t('nav.book')) ?></a>
                <details class="lang-switch">
                    <summary>
                        <span><?= e(I18n::FLAGS[current_lang()]) ?> <?= e(I18n::LABELS[current_lang()]) ?></span>
                    </summary>
                    <div class="lang-menu">
                        <?php foreach (I18n::LANGS as $code): ?>
                            <a href="<?= e(url_for($page ?? 'home', [], $code)) ?>" <?= $code === current_lang() ? 'aria-current="true"' : '' ?>>
                                <?= e(I18n::FLAGS[$code]) ?> <?= e(I18n::LABELS[$code]) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </details>
                <a class="nav-social" href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram">
                    <img src="<?= e(asset('img/logo/instagram.png')) ?>" alt="">
                </a>
                <button class="menu-btn" type="button" aria-expanded="false" aria-controls="nav" data-menu>
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>
    <main id="main">
        <?= $content ?>
    </main>
    <footer class="site-footer">
        <div class="wrap footer-grid">
            <div>
                <img class="footer-logo" src="<?= e(asset('img/logo/logo-gold.jpg')) ?>" alt="">
                <p class="footer-name">Villa Mariolu Beach</p>
                <p><?= e(setting('address')) ?></p>
                <p><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p>
            </div>
            <div>
                <p class="footer-title"><?= e(t('nav.home')) ?></p>
                <a href="<?= e(url_for('home')) ?>#villa"><?= e(t('nav.villa')) ?></a>
                <a href="<?= e(url_for('reserver')) ?>"><?= e(t('nav.book')) ?></a>
                <a href="<?= e(url_for('home')) ?>#budoni"><?= e(t('nav.budoni')) ?></a>
                <a href="<?= e(url_for('home')) ?>#faq"><?= e(t('nav.faq')) ?></a>
                <a href="<?= e(url_for('mentions')) ?>"><?= e(t('footer.legal')) ?></a>
            </div>
            <div>
                <p class="footer-title">Follow</p>
                <a href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener">Instagram</a>
                <a href="<?= e(setting('facebook')) ?>" target="_blank" rel="noopener">Facebook</a>
                <a href="<?= e(setting('google_reviews')) ?>" target="_blank" rel="noopener">Google</a>
            </div>
        </div>
        <div class="wrap footer-legal">
            <p><?= e(t('footer.iun')) ?></p>
            <p><?= e(t('footer.registry')) ?></p>
            <p><?= e(t('footer.rights')) ?> · <a href="<?= e(url_for('mentions')) ?>"><?= e(t('cookies.title')) ?></a> · <a href="<?= e(base_url('admin')) ?>"><?= e(t('footer.admin')) ?></a></p>
        </div>
    </footer>
    <div class="cookie-banner" id="cookie-banner" role="dialog" aria-label="<?= e(t('cookies.title')) ?>">
        <div class="cookie-banner-inner">
            <p><strong><?= e(t('cookies.title')) ?></strong> <?= e(t('cookies.text')) ?>
                <a href="<?= e(url_for('mentions')) ?>#cookies"><?= e(t('cookies.more')) ?></a>
            </p>
            <button type="button" class="btn btn-gold" data-cookie-accept><?= e(t('cookies.accept')) ?></button>
        </div>
    </div>
    <a class="back-top" href="#top" data-back-top hidden aria-label="<?= e(t('ui.back_top')) ?>"><?= e(t('ui.back_top')) ?></a>
    <div class="lightbox" id="lightbox" hidden>
        <button type="button" class="lightbox-close" data-lightbox-close aria-label="Close">&times;</button>
        <button type="button" class="lightbox-nav lightbox-prev" data-lightbox-prev aria-label="<?= e(t('gallery.prev')) ?>">‹</button>
        <img alt="">
        <button type="button" class="lightbox-nav lightbox-next" data-lightbox-next aria-label="<?= e(t('gallery.next')) ?>">›</button>
    </div>
    <script src="<?= e(asset('js/app.js')) ?>?v=<?= e((string) filemtime(ROOT . '/assets/js/app.js')) ?>"></script>
    <script src="<?= e(asset('js/calendar.js')) ?>?v=<?= e((string) filemtime(ROOT . '/assets/js/calendar.js')) ?>"></script>
    <script src="<?= e(asset('js/booking.js')) ?>"></script>
</body>
</html>
