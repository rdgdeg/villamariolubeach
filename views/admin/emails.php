<?php
$kinds = EmailTemplates::kinds();
$kind = (string) ($kind ?? 'request');
$templates = $templates ?? EmailTemplates::forKind($kind);
$tokens = EmailTemplates::tokens();
?>
<form method="post" class="card stack">
    <?= Csrf::field() ?>
    <input type="hidden" name="form" value="delays">
    <h2>Délais des relances</h2>
    <p class="hint">Enregistrés dès maintenant. L’envoi automatique se branchera plus tard (phase 2). En attendant, utilisez les boutons sur la fiche réservation.</p>
    <div class="row">
        <label>Relance acompte 1 (jours après confirmation)
            <input type="number" min="1" max="365" name="mail_deposit_remind_days_1" value="<?= e(setting('mail_deposit_remind_days_1', '3')) ?>">
        </label>
        <label>Relance acompte 2 (jours après confirmation)
            <input type="number" min="1" max="365" name="mail_deposit_remind_days_2" value="<?= e(setting('mail_deposit_remind_days_2', '10')) ?>">
        </label>
        <label>Demande de solde (jours avant arrivée)
            <input type="number" min="1" max="365" name="mail_balance_days_1" value="<?= e(setting('mail_balance_days_1', '60')) ?>">
        </label>
        <label>Relance solde (jours avant arrivée)
            <input type="number" min="1" max="365" name="mail_balance_days_2" value="<?= e(setting('mail_balance_days_2', '30')) ?>">
        </label>
    </div>
    <button type="submit">Enregistrer les délais</button>
</form>

<div class="email-layout">
    <nav class="email-kinds card">
        <?php foreach ($kinds as $key => $label): ?>
            <a class="<?= $key === $kind ? 'is-active' : '' ?>" href="<?= e(base_url('admin/emails?kind=' . rawurlencode($key))) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="card stack" data-email-editor>
        <h2><?= e($kinds[$kind] ?? $kind) ?></h2>
        <p class="hint">Un enregistrement sauve les 6 langues. Cliquez un champ auto pour l’insérer dans le texte actif.</p>
        <div class="token-chips">
            <?php foreach ($tokens as $token => $label): ?>
                <button type="button" data-insert-token="<?= e($token) ?>"><?= e($label) ?> <code><?= e($token) ?></code></button>
            <?php endforeach; ?>
        </div>
        <div class="email-tabs" role="tablist">
            <?php foreach (I18n::LANGS as $i => $lang): ?>
                <button type="button" class="<?= $i === 0 ? 'is-active' : '' ?>" data-email-tab="<?= e($lang) ?>">
                    <?= e(I18n::FLAGS[$lang] ?? '') ?> <?= e(I18n::LABELS[$lang] ?? $lang) ?>
                </button>
            <?php endforeach; ?>
        </div>
        <form method="post" class="stack">
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="save">
            <input type="hidden" name="kind" value="<?= e($kind) ?>">
            <?php foreach (I18n::LANGS as $i => $lang): ?>
                <div class="email-pane <?= $i === 0 ? 'is-active' : '' ?>" data-email-pane="<?= e($lang) ?>">
                    <label>Objet
                        <input name="subjects[<?= e($lang) ?>]" value="<?= e($templates[$lang]['subject'] ?? '') ?>">
                    </label>
                    <label>Texte
                        <textarea name="bodies[<?= e($lang) ?>]" rows="18"><?= e($templates[$lang]['body'] ?? '') ?></textarea>
                    </label>
                </div>
            <?php endforeach; ?>
            <div class="row">
                <button type="submit">Enregistrer ce modèle</button>
            </div>
        </form>
        <form method="post" class="row" data-email-test>
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="test">
            <input type="hidden" name="kind" value="<?= e($kind) ?>">
            <input type="hidden" name="lang" value="fr" data-email-test-lang>
            <button type="submit" class="btn-secondary">Envoyer un test (langue affichée → <?= e(setting('email')) ?>)</button>
        </form>
    </div>
</div>
