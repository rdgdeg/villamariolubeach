<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> · Admin Villa Mariolu</title>
    <link rel="icon" href="<?= e(asset('img/logo/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Roboto:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>?v=<?= e((string) filemtime(ROOT . '/assets/css/admin.css')) ?>">
</head>
<body class="<?= empty($adminUser) ? 'login-body' : '' ?>">
<?php if (!empty($adminUser)): ?>
    <?php
    $navAction = $action ?? '';
    $navIcon = static function (string $name): string {
        $paths = [
            'home' => '<path d="M4 10.2 12 3.5l8 6.7V20a1 1 0 0 1-1 1h-5.2v-6.2h-3.6V21H5a1 1 0 0 1-1-1z"/>',
            'bookings' => '<rect x="5" y="4.5" width="14" height="16" rx="2"/><path d="M8 2.8v3M16 2.8v3M5 9h14"/><path d="M9 13h.01M12 13h.01M15 13h.01M9 16.5h.01M12 16.5h.01M15 16.5h.01"/>',
            'pricing' => '<path d="M12 3v18M16.4 7.2c-.8-1.3-2.2-2-4.3-2-3 0-5.1 1.7-5.1 4.1 0 5.7 9.4 2.4 9.4 7.2 0 2.2-2 4-5.2 4-2.3 0-4-.8-5-2.3"/>',
            'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3.2v3.5M16 3.2v3.5M4 10h16M8.5 14h3M13.5 14h2M8.5 17h2"/>',
            'messages' => '<path d="M4.5 6.5h15v11.2a1.6 1.6 0 0 1-1.6 1.6H6.1A1.6 1.6 0 0 1 4.5 17.7z"/><path d="m4.8 7 7.2 6.2L19.2 7"/>',
            'emails' => '<rect x="4" y="6" width="16" height="12" rx="2"/><path d="m4.6 7.2 7.4 5.6 7.4-5.6"/>',
            'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 3.4v2.2M12 18.4v2.2M4.8 7.2l1.9 1.1M17.3 15.7l1.9 1.1M4.8 16.8l1.9-1.1M17.3 8.3l1.9-1.1M3.4 12h2.2M18.4 12h2.2"/>',
            'backup' => '<path d="M12 3v10"/><path d="m8.5 9.5 3.5 3.5 3.5-3.5"/><rect x="4" y="16" width="16" height="5" rx="1.2"/>',
            'site' => '<path d="M10 5H6.2A2.2 2.2 0 0 0 4 7.2v10.6A2.2 2.2 0 0 0 6.2 20h10.6A2.2 2.2 0 0 0 19 17.8V14"/><path d="M13 4h7v7M20 4l-9 9"/>',
            'logout' => '<path d="M10 12h10M16.5 8.5 20 12l-3.5 3.5"/><path d="M13 5H7.2A2.2 2.2 0 0 0 5 7.2v9.6A2.2 2.2 0 0 0 7.2 19H13"/>',
        ];
        $d = $paths[$name] ?? '';
        return '<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
    };
    $navClass = static function (array $keys) use ($navAction): string {
        return in_array($navAction, $keys, true) ? 'nav-link is-active' : 'nav-link';
    };
    ?>
    <aside class="admin-nav">
        <a class="brand" href="<?= e(base_url('admin')) ?>">
            <?= $navIcon('home') ?>
            <span>Villa Mariolu</span>
        </a>
        <nav class="admin-nav-list">
            <a class="<?= e($navClass(['dashboard', 'booking', ''])) ?>" href="<?= e(base_url('admin')) ?>">
                <?= $navIcon('bookings') ?>
                <span>Réservations</span>
            </a>
            <a class="<?= e($navClass(['pricing'])) ?>" href="<?= e(base_url('admin/pricing')) ?>">
                <?= $navIcon('pricing') ?>
                <span>Grille tarifaire</span>
            </a>
            <a class="<?= e($navClass(['calendar'])) ?>" href="<?= e(base_url('admin/calendar')) ?>">
                <?= $navIcon('calendar') ?>
                <span>Calendrier</span>
            </a>
            <a class="<?= e($navClass(['messages'])) ?>" href="<?= e(base_url('admin/messages')) ?>">
                <?= $navIcon('messages') ?>
                <span>Messages</span>
            </a>
            <a class="<?= e($navClass(['emails'])) ?>" href="<?= e(base_url('admin/emails')) ?>">
                <?= $navIcon('emails') ?>
                <span>E-mails</span>
            </a>
            <a class="<?= e($navClass(['settings'])) ?>" href="<?= e(base_url('admin/settings')) ?>">
                <?= $navIcon('settings') ?>
                <span>Paramètres</span>
            </a>
            <a class="<?= e($navClass(['backup'])) ?>" href="<?= e(base_url('admin/backup')) ?>">
                <?= $navIcon('backup') ?>
                <span>Sauvegardes</span>
            </a>
        </nav>
        <div class="admin-nav-foot">
            <a class="nav-link" href="<?= e(base_url()) ?>" target="_blank" rel="noopener">
                <?= $navIcon('site') ?>
                <span>Voir le site</span>
            </a>
            <a class="nav-link is-logout" href="<?= e(base_url('admin/logout')) ?>">
                <?= $navIcon('logout') ?>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>
    <main class="admin-main">
        <header class="admin-top">
            <h1><?= e($title) ?></h1>
            <span><?= e($adminUser['username']) ?></span>
        </header>
        <?php if ($msg = flash('success')): ?><p class="flash ok"><?= e($msg) ?></p><?php endif; ?>
        <?php if ($msg = flash('error')): ?><p class="flash err"><?= e($msg) ?></p><?php endif; ?>
        <?= $content ?>
    </main>
    <dialog class="stay-dialog" id="confirm-stay-dialog">
        <form method="dialog" class="stay-dialog-card">
            <h3>Confirmer la réservation</h3>
            <p data-confirm-stay-copy></p>
            <label>Montant de l’acompte (€)
                <input type="number" min="0" step="0.01" data-confirm-stay-amount required>
            </label>
            <p class="hint">L’e-mail d’acompte partira avec ce montant. Modifiez-le si besoin.</p>
            <div class="stay-dialog-actions">
                <button value="cancel">Annuler</button>
                <button value="ok">Confirmer et envoyer</button>
            </div>
        </form>
    </dialog>
    <dialog class="stay-dialog mail-dialog" id="mail-compose-dialog">
        <form method="dialog" class="stay-dialog-card mail-compose-card">
            <h3 data-mail-title>E-mail</h3>
            <p class="hint" data-mail-status></p>
            <label>Destinataire
                <input type="email" data-mail-to required>
            </label>
            <label>Objet
                <input data-mail-subject required>
            </label>
            <label>Message
                <textarea data-mail-body rows="16" required></textarea>
            </label>
            <div class="stay-dialog-actions">
                <button value="cancel">Annuler</button>
                <button value="ok" data-mail-send disabled>Envoyer</button>
            </div>
        </form>
    </dialog>
    <script>window.VMB = {
        lang: 'fr',
        admin: true,
        adminBase: <?= json_encode(base_url('admin')) ?>,
        api: <?= json_encode(base_url('api')) ?>,
        csrf: <?= json_encode(Csrf::token()) ?>,
        i18n: {
            weekdays: <?= json_encode(t_arr('calendar.weekdays')) ?>,
            months: <?= json_encode(t_arr('calendar.months')) ?>,
            turnover: <?= json_encode(t('calendar.turnover')) ?>,
            arrival_day: <?= json_encode(t('calendar.arrival_day')) ?>,
            errors: {}
        }
    };</script>
    <script src="<?= e(asset('js/calendar.js')) ?>?v=<?= e((string) filemtime(ROOT . '/assets/js/calendar.js')) ?>"></script>
    <script src="<?= e(asset('js/admin.js')) ?>?v=<?= e((string) filemtime(ROOT . '/assets/js/admin.js')) ?>"></script>
    <?php if ($mailto = flash('mailto')): ?>
        <script>window.location.href = <?= json_encode($mailto) ?>;</script>
    <?php endif; ?>
<?php else: ?>
    <?= $content ?>
<?php endif; ?>
</body>
</html>
